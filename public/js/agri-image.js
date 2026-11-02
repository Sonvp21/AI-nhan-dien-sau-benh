/*
 * Tien ich cho viec gui anh chan doan, dung chung cho trang chinh (agri-app.js) va trang chup tu xa
 * (agri-remote-capture.blade.php).
 *
 *  - agriShrinkImage(file): thu nho anh truoc khi gui len AI. Anh dien thoai thuong 4-8 MB, tren 4G
 *    yeu o nong thon gui rat lau; AI chi can ~1024px la thay du dom benh. Loi o buoc nay thi gui
 *    nguyen anh goc, khong bao gio lam hong luong chan doan.
 *  - agriDiagnoseRequest(url, file, cropKey, onStage): goi /predict co han chot thoi gian, bao tien do
 *    tung giai doan, va doi loi may chu thanh cau tieng Viet de hien cho nguoi dung.
 */
(function(){
  var MAX_SIDE = 1280;
  var SKIP_UNDER_BYTES = 350 * 1024;

  window.agriShrinkImage = async function(file){
    try{
      if(!file || !file.type || file.type.indexOf('image/') !== 0 || file.size < SKIP_UNDER_BYTES) return file;
      var bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
      var scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
      var w = Math.round(bitmap.width * scale), h = Math.round(bitmap.height * scale);
      var canvas = document.createElement('canvas');
      canvas.width = w; canvas.height = h;
      canvas.getContext('2d').drawImage(bitmap, 0, 0, w, h);
      if(bitmap.close) bitmap.close();
      var blob = await new Promise(function(resolve){ canvas.toBlob(resolve, 'image/jpeg', 0.85); });
      // Chi dung ban thu nho neu that su nho hon
      if(!blob || blob.size >= file.size) return file;
      return new File([blob], (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
    }catch(e){
      return file;
    }
  };

  // Cac moc thoi gian (giay) -> cau hien cho nguoi dung trong luc cho AI
  var STAGES = [
    [0,  'Đang chuẩn bị ảnh...'],
    [2,  'Đang gửi ảnh cho AI...'],
    [4,  'AI đang quan sát lá cây...'],
    [10, 'AI đang viết hướng dẫn chữa trị...'],
    [30, 'Hơi lâu hơn bình thường, vui lòng chờ thêm...']
  ];
  var TIMEOUT_MS = 75000;

  window.agriDiagnoseRequest = async function(url, file, cropKey, onStage){
    var timers = [];
    var report = function(text){ if(onStage) onStage(text); };
    STAGES.forEach(function(s){ timers.push(setTimeout(function(){ report(s[1]); }, s[0] * 1000)); });
    var controller = new AbortController();
    var abortTimer = setTimeout(function(){ controller.abort(); }, TIMEOUT_MS);
    try{
      var small = await window.agriShrinkImage(file);
      var form = new FormData();
      form.append('file', small);
      form.append('crop', cropKey);
      var res = await fetch(url, { method: 'POST', body: form, signal: controller.signal });
      if(!res.ok){
        var message = '';
        try{ message = (await res.json()).detail || ''; }catch(e){}
        var error = new Error('API lỗi: ' + res.status);
        // Chi hien nguyen van khi may chu tra cau tieng Viet (vd "Ban gui anh hoi nhieu..."), con lai dung cau chung
        error.userMessage = (typeof message === 'string' && /[ạảãàáâậầấẩẫăắằặẳẵêệềếểễôộồốổỗơợờớởỡưựừứửữđ]/i.test(message))
          ? message
          : 'Không kết nối được tới AI server, vui lòng thử lại sau.';
        throw error;
      }
      return await res.json();
    }catch(e){
      if(e && e.name === 'AbortError'){
        e.userMessage = 'AI phản hồi quá lâu, vui lòng thử lại.';
      }else if(!e.userMessage){
        e.userMessage = 'Không kết nối được tới AI server, vui lòng kiểm tra mạng rồi thử lại.';
      }
      throw e;
    }finally{
      timers.forEach(clearTimeout);
      clearTimeout(abortTimer);
    }
  };
})();
