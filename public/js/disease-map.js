// ================= disease-map.js =================
// Logic bản đồ dịch bệnh DÙNG CHUNG cho trang công khai (agri-disease-map.blade.php)
// và dashboard vùng dịch admin (admin/disease-zones/index.blade.php). Gồm:
//   - Marker tuỳ chỉnh: 1 hình tròn viền màu theo MỨC ĐỘ bệnh (nhẹ/vừa/nặng),
//     bên trong là ẢNH THẬT của cây trồng (public/image/crop-*.png|jpg - đúng
//     ảnh dùng ở khu "Chọn cây trồng" của agri-app.js), không dùng icon vector
//     hay emoji nữa. Ảnh được ghép vào viền màu bằng <canvas> (crop tròn kiểu
//     "cover"), kết quả cache lại theo cropKey+màu để không phải vẽ lại mỗi
//     lần tạo marker - trang gọi PHẢI đợi preloadCropImages() xong (Promise)
//     trước khi gọi markerIcon() lần đầu.
//   - Heatmap layer thể hiện trực quan "vùng dịch" (nơi tập trung nhiều điểm
//     phát hiện bệnh). Google đã GỠ BỎ HẲN google.maps.visualization.HeatmapLayer
//     khỏi Maps JavaScript API (xem https://developers.google.com/maps/deprecations)
//     nên chuyển sang deck.gl (deck.HeatmapLayer + deck.GoogleMapsOverlay,
//     đúng theo hướng dẫn migrate chính thức của Google) - trang gọi phải nạp
//     thêm 2 script deck.gl + @deck.gl/google-maps TRƯỚC file này (xem
//     agri-disease-map.blade.php / admin/disease-zones/index.blade.php).
//   - Nội dung popup marker dùng chung 1 template, có thể kèm nút "Đánh dấu
//     đã xử lý" khi dùng ở trang admin (options.showResolveButton).
// Không dùng module/bundler - gắn thẳng vào window.DiseaseMapKit để cả 2 trang
// (1 trang blade thường, 1 trang admin) include bằng <script> bình thường.

window.DiseaseMapKit = (function () {
  var CROP_META = {
    che: { label: 'Chè', img: '/image/crop-che.png' },
    lua: { label: 'Lúa', img: '/image/crop-lua.png' },
    ngo: { label: 'Ngô', img: '/image/crop-ngo.png' },
    san: { label: 'Sắn', img: '/image/crop-san.png' },
    ca_chua: { label: 'Cà chua', img: '/image/crop-cachua.png' },
    ot: { label: 'Ớt', img: '/image/crop-ot.jpg' },
    xoai: { label: 'Xoài', img: '/image/crop-xoai.jpg' },
  };

  function levelColor(level) {
    if (level === 'Nặng') return '#c1440e';
    if (level === 'Trung bình') return '#c9762c';
    return '#1f6d3c';
  }

  // Tải trước ảnh cây trồng thật (7 ảnh, ~vài chục KB - vài MB) vào 1
  // <img> ẩn/HTMLImageElement giữ trong bộ nhớ để markerIcon() vẽ canvas
  // đồng bộ (không cần chờ mỗi lần vẽ marker). Trang gọi initDiseaseMap()
  // phải gọi hàm này (trả về Promise) TRƯỚC khi vẽ marker lần đầu tiên.
  // Ảnh lỗi (onerror) vẫn resolve - markerIcon() sẽ tự rơi về icon màu trơn.
  var _imgCache = {};
  var _preloadPromise = null;
  function preloadCropImages() {
    if (_preloadPromise) return _preloadPromise;
    var keys = Object.keys(CROP_META);
    _preloadPromise = Promise.all(keys.map(function (key) {
      return new Promise(function (resolve) {
        var img = new Image();
        img.onload = function () { _imgCache[key] = img; resolve(); };
        img.onerror = function () { resolve(); };
        img.src = CROP_META[key].img;
      });
    }));
    return _preloadPromise;
  }

  // Icon marker màu trơn (fallback khi ảnh cây chưa tải xong / lỗi tải) -
  // hình tròn viền trắng, nền màu theo mức độ.
  function _plainColorIcon(color, size) {
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '">' +
      '<circle cx="' + (size / 2) + '" cy="' + (size / 2) + '" r="' + (size / 2 - 1.5) + '" fill="' + color + '" stroke="#ffffff" stroke-width="2.5"/>' +
    '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
  }

  // Icon marker: hình tròn viền màu theo mức độ bệnh, bên trong là ẢNH THẬT
  // của cây trồng (crop kiểu "cover" cho vừa khung tròn) - ghép bằng canvas,
  // cache lại theo cropKey+màu - trả về đúng format Google Maps Marker.icon
  // cần (url/scaledSize/anchor).
  var _iconCache = {};
  function markerIcon(cropKey, level) {
    var color = levelColor(level);
    var size = 40;
    var cacheKey = cropKey + '|' + color;
    if (_iconCache[cacheKey]) return _iconCache[cacheKey];

    var img = _imgCache[cropKey];
    var url;
    try {
      if (!img) throw new Error('image not ready');
      var canvas = document.createElement('canvas');
      canvas.width = size;
      canvas.height = size;
      var ctx = canvas.getContext('2d');
      var cx = size / 2, cy = size / 2;
      var outerR = size / 2 - 1;
      var innerR = outerR - 3.5; // để lại viền màu dày ~3.5px quanh ảnh

      // Vòng viền ngoài: màu theo mức độ bệnh.
      ctx.beginPath();
      ctx.arc(cx, cy, outerR, 0, Math.PI * 2);
      ctx.fillStyle = color;
      ctx.fill();

      // Ảnh cây trồng thật, crop tròn kiểu "cover" ở giữa.
      ctx.save();
      ctx.beginPath();
      ctx.arc(cx, cy, innerR, 0, Math.PI * 2);
      ctx.clip();
      var scale = Math.max((innerR * 2) / img.naturalWidth, (innerR * 2) / img.naturalHeight);
      var w = img.naturalWidth * scale, h = img.naturalHeight * scale;
      ctx.drawImage(img, cx - w / 2, cy - h / 2, w, h);
      ctx.restore();

      url = canvas.toDataURL('image/png');
    } catch (e) {
      // Ảnh chưa tải xong / lỗi CORS hiếm gặp - rơi về icon màu trơn để
      // không làm vỡ bản đồ.
      url = _plainColorIcon(color, size);
    }

    var icon = {
      url: url,
      scaledSize: new google.maps.Size(size, size),
      anchor: new google.maps.Point(size / 2, size / 2),
    };
    _iconCache[cacheKey] = icon;
    return icon;
  }

  // Nội dung HTML cho InfoWindow của 1 report - dùng chung, chỉ khác nút hành
  // động cuối (options.showResolveButton -> có thêm nút "Đánh dấu đã xử lý",
  // options.onResolveAttr -> chuỗi onclick gắn vào nút đó, do trang gọi tự set)
  // và nút "Xem chi tiết" (options.detailAttr -> tên hàm onclick, mặc định
  // 'openReportDetail' - trang preview không có modal chi tiết đầy đủ thì tự
  // set tên hàm khác, ví dụ redirect sang dashboard vùng dịch).
  function buildPopupHtml(r, options) {
    options = options || {};
    var html =
      '<div style="width:230px;font-family:\'Be Vietnam Pro\',Inter,sans-serif">' +
        '<img src="' + r.image_url + '" style="width:100%;height:110px;object-fit:cover;border-radius:10px;margin-bottom:8px" onerror="this.style.display=\'none\'">' +
        '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">' +
          '<p style="font-weight:700;margin:0;color:#12341d;font-size:13.5px">' + r.disease_name + '</p>' +
          (r.level ? '<span style="font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:99px;background:' + levelColor(r.level) + '1a;color:' + levelColor(r.level) + '">' + r.level + '</span>' : '') +
        '</div>' +
        '<p style="font-size:11.5px;color:#8a8f83;margin:3px 0 10px">' + r.crop_label + ' · ' + r.date +
          (r.probability !== null && r.probability !== undefined ? ' · ' + r.probability + '%' : '') +
        '</p>' +
        '<button onclick="' + (options.detailAttr || 'openReportDetail') + '(' + r.id + ')" style="width:100%;padding:7px 0;border-radius:8px;border:none;background:#1f6d3c;color:#fff;font-size:12.5px;font-weight:600;cursor:pointer;margin-bottom:6px">Xem chi tiết</button>' +
        (options.showResolveButton
          ? '<button onclick="' + options.onResolveAttr + '(' + r.id + ')" style="width:100%;padding:7px 0;border-radius:8px;border:1px solid #dbe8d2;background:#fff;color:#4a5245;font-size:12.5px;font-weight:600;cursor:pointer">Đánh dấu đã xử lý</button>'
          : '') +
      '</div>';
    return html;
  }

  // Heatmap layer (chưa gắn vào map, gọi .setMap(map) để hiện, .setMap(null)
  // để ẩn - dùng cho switch "Lớp mật độ" trên cả 2 trang). Google đã GỠ HẲN
  // google.maps.visualization.HeatmapLayer khỏi Maps JavaScript API (xem
  // https://developers.google.com/maps/deprecations) nên chuyển sang deck.gl:
  // deck.HeatmapLayer bọc trong deck.GoogleMapsOverlay - overlay này có sẵn
  // .setMap()/.setMap(null) giống 1 overlay bản đồ thường nên code Ở 2 TRANG
  // GỌI HÀM NÀY KHÔNG CẦN ĐỔI GÌ.
  //
  // aggregation:'MEAN' + colorDomain CỐ ĐỊNH [1,3] (thay vì để deck.gl tự
  // tính theo giá trị lớn nhất ĐANG THẤY TRONG KHUNG NHÌN HIỆN TẠI - đây là
  // hành vi mặc định của deck.gl khi không set colorDomain, xem docs
  // https://deck.gl/docs/api-reference/aggregation-layers/heatmap-layer#colordomain):
  // đó chính là lý do lớp mật độ trước đây ẨN khi xem toàn tỉnh (nhiều vùng
  // dịch cùng lúc trong khung nhìn khiến vùng yếu hơn bị tính "thấp hơn
  // threshold" nên mờ hẳn) và chỉ HIỆN RÕ khi zoom vào 1 vùng - zoom vào thì
  // vùng đó thành vùng "cao nhất" duy nhất trong khung nhìn nên mới sáng lên.
  // Cố định domain theo đúng thang trọng số (Nhẹ=1..Nặng=3) làm màu ổn định
  // ở MỌI mức zoom, không phụ thuộc đang xem bao nhiêu vùng dịch cùng lúc.
  function createHeatmap(reports) {
    var weightByLevel = { 'Nhẹ': 1, 'Trung bình': 2, 'Nặng': 3 };
    var data = reports.map(function (r) {
      return {
        lat: parseFloat(r.lat),
        lng: parseFloat(r.lng),
        weight: weightByLevel[r.level] || 1,
      };
    });

    var heatmap = new deck.HeatmapLayer({
      id: 'disease-heatmap',
      data: data,
      aggregation: 'MEAN',
      colorDomain: [1, 3],
      getPosition: function (d) { return [d.lng, d.lat]; },
      getWeight: function (d) { return d.weight; },
      radiusPixels: 50,
      opacity: 0.8,
      colorRange: [
        [255, 226, 190],
        [249, 189, 128],
        [240, 148, 82],
        [225, 100, 51],
        [193, 68, 14],
        [120, 30, 6],
      ],
    });

    return new deck.GoogleMapsOverlay({ layers: [heatmap] });
  }

  // ================= Panel điều khiển nổi trên bản đồ =================
  // Dùng chung cho cả 2 trang: 1 nút tròn nổi mở popup gồm 3 nhóm - đổi loại
  // bản đồ, lọc theo cây (dropdown thay cho <select>), bật/tắt lớp dữ liệu
  // (switch thay cho checkbox). Trang gọi initControlPanel() truyền vào
  // getMap() (lấy instance map hiện tại) + 2 callback khi người dùng đổi lọc
  // cây / bật-tắt lớp, tự lo phần loadReports()/setMap() riêng của trang.
  // Trả về 1 object state {crop, heatmapOn, markersOn} để trang đọc khi cần
  // (ví dụ nút "Đánh dấu đã xử lý toàn bộ" ở dashboard admin).
  function initControlPanel(opts) {
    opts = opts || {};
    var onCropChange = opts.onCropChange || function () {};
    var onLayerToggle = opts.onLayerToggle || function () {};
    var getMap = opts.getMap || function () { return null; };
    var state = { crop: '', heatmapOn: true, markersOn: true };

    var btn = document.getElementById('btnMapControl');
    var panel = document.getElementById('mapControlPanel');
    var closeBtn = document.getElementById('btnCloseControlPanel');
    if (!btn || !panel) return state;

    // Panel luôn hiện mặc định (đã set sẵn trong markup) - nút tròn nổi dùng
    // để mở lại sau khi đã đóng, nút X ở header dùng để đóng. KHÔNG tự đóng
    // khi bấm ra ngoài nữa (chỉ đóng qua nút X/nút nổi) - riêng dropdown lọc
    // cây (menu con bên trong panel) vẫn tự đóng khi bấm ra ngoài nó.
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      panel.style.display = (panel.style.display === 'none') ? 'block' : 'none';
    });
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.style.display = 'none';
      });
    }

    // -- Nhóm 1: đổi loại bản đồ --
    var typeButtons = panel.querySelectorAll('.maptype-btn');
    typeButtons.forEach(function (b) {
      b.addEventListener('click', function () {
        var map = getMap();
        if (map) map.setMapTypeId(b.getAttribute('data-maptype-btn'));
        typeButtons.forEach(function (x) { x.setAttribute('data-active', x === b ? 'true' : 'false'); });
      });
    });

    // -- Nhóm 2: lọc theo cây (dropdown) --
    var cropBtn = document.getElementById('btnCropDropdown');
    var cropDropdown = document.getElementById('cropDropdown');
    var cropLabel = document.getElementById('cropFilterLabel');
    if (cropBtn && cropDropdown) {
      cropBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        cropDropdown.style.display = (cropDropdown.style.display === 'block') ? 'none' : 'block';
      });
      document.addEventListener('click', function (e) {
        if (!cropDropdown.contains(e.target) && e.target !== cropBtn) cropDropdown.style.display = 'none';
      });
    }
    panel.querySelectorAll('.crop-option').forEach(function (opt) {
      opt.addEventListener('click', function () {
        var crop = opt.getAttribute('data-crop') || '';
        state.crop = crop;
        panel.querySelectorAll('.crop-option').forEach(function (o) { o.setAttribute('data-active', o === opt ? 'true' : 'false'); });
        if (cropLabel) cropLabel.textContent = opt.textContent;
        if (cropDropdown) cropDropdown.style.display = 'none';
        onCropChange(crop);
      });
    });

    // -- Nhóm 3: bật/tắt lớp dữ liệu (switch) --
    function wireSwitch(id, stateKey, layerName) {
      var el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('click', function () {
        var on = el.getAttribute('data-on') !== 'true';
        el.setAttribute('data-on', on ? 'true' : 'false');
        state[stateKey] = on;
        onLayerToggle(layerName, on);
      });
    }
    wireSwitch('layerHeatmapSwitch', 'heatmapOn', 'heatmap');
    wireSwitch('layerMarkersSwitch', 'markersOn', 'markers');

    return state;
  }

  // Đặt sẵn UI dropdown lọc cây (label + trạng thái active) khớp với 1 crop
  // key cho trước - dùng khi trang mở kèm ?crop=... (không tự gọi callback,
  // trang tự gọi loadReports() sau khi set state.crop tương ứng).
  function setControlPanelCropUI(cropKey) {
    var opt = document.querySelector('.crop-option[data-crop="' + cropKey + '"]');
    if (!opt) return false;
    document.querySelectorAll('.crop-option').forEach(function (o) { o.setAttribute('data-active', o === opt ? 'true' : 'false'); });
    var label = document.getElementById('cropFilterLabel');
    if (label) label.textContent = opt.textContent;
    return true;
  }

  // Nút nổi "Chú giải" - panel luôn hiện mặc định (đã set sẵn trong markup),
  // gồm 2 phần: (1) màu viền marker theo MỨC ĐỘ bệnh, (2) ảnh đại diện từng
  // LOẠI CÂY (lấy trực tiếp từ CROP_META nên luôn khớp với ảnh marker thật
  // trên bản đồ - không cần khai báo lại ở Blade). Đóng bằng nút X ở header
  // hoặc bấm lại nút tròn nổi để mở/đóng - KHÔNG tự đóng khi bấm ra ngoài.
  function initLegend() {
    var btn = document.getElementById('btnMapLegend');
    var panel = document.getElementById('mapLegendPanel');
    var closeBtn = document.getElementById('btnCloseLegend');
    var list = document.getElementById('legendCropList');
    if (!btn || !panel) return;

    if (list && !list.dataset.filled) {
      list.innerHTML = Object.keys(CROP_META).map(function (key) {
        var meta = CROP_META[key];
        return '<div style="display:flex;align-items:center;gap:8px;padding:3px 0">' +
          '<span style="width:20px;height:20px;border-radius:999px;background-image:url(\'' + meta.img + '\');background-size:cover;background-position:center;flex-shrink:0;border:1px solid #eceae3"></span>' +
          '<span style="font-size:12.5px;font-weight:500;color:#3d443a">' + meta.label + '</span>' +
        '</div>';
      }).join('');
      list.dataset.filled = 'true';
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      panel.style.display = (panel.style.display === 'none') ? 'block' : 'none';
    });
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.style.display = 'none';
      });
    }
  }

  // Wiring dùng chung cho các nút nổi dạng đơn giản: bấm nút tròn để mở/đóng
  // 1 popup, có nút X riêng để đóng, KHÔNG tự đóng khi bấm ra ngoài (giống
  // panel Chú giải/Điều khiển) - dùng cho panel "Thống kê vùng dịch" ở
  // dashboard admin. Không quản lý nội dung popup (trang tự đổ dữ liệu và tự
  // gắn sự kiện cho các dòng bên trong).
  function initSimplePanel(btnId, panelId, closeBtnId) {
    var btn = document.getElementById(btnId);
    var panel = document.getElementById(panelId);
    var closeBtn = closeBtnId ? document.getElementById(closeBtnId) : null;
    if (!btn || !panel) return;

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
    });
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.style.display = 'none';
      });
    }
  }

  return {
    CROP_META: CROP_META,
    levelColor: levelColor,
    preloadCropImages: preloadCropImages,
    markerIcon: markerIcon,
    buildPopupHtml: buildPopupHtml,
    initLegend: initLegend,
    initSimplePanel: initSimplePanel,
    createHeatmap: createHeatmap,
    initControlPanel: initControlPanel,
    setControlPanelCropUI: setControlPanelCropUI,
  };
})();
