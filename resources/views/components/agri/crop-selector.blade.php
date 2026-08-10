{{-- ================= CHỌN MÔ HÌNH CÂY: box riêng, nằm trên cùng cột chụp ảnh =================
     Responsive: mobile (mặc định) / HD (md:) / FHD (2xl:) --}}
<div class="rounded-xl p-4 md:p-5 2xl:p-6" style="background:#fff;border:1px solid #eceae3">
  <p class="text-[17px] md:text-[18px] 2xl:text-[20px] font-bold mb-3" style="color:#12341d">Chọn mô hình cây trồng</p>
  <div class="grid grid-cols-5 gap-2 md:gap-3 2xl:gap-4">
    <template x-for="c in crops" :key="c.name">
      <button @click="selectCrop(c.name)" class="rounded-xl overflow-hidden transition" :class="selectedCrop===c.name ? '' : 'opacity-55'"
              :style="selectedCrop===c.name ? 'border:2px solid #1f6d3c' : 'border:2px solid transparent'">
        <!-- vùng ảnh: ảnh đại diện từng cây -->
        <div class="h-12 md:h-14 2xl:h-16 overflow-hidden" style="background:#f2f7ee">
          <img :src="c.img" :alt="c.name" class="w-full h-full object-cover">
        </div>
        <!-- vùng chữ: tách riêng khỏi ảnh -->
        <div class="py-1" :style="selectedCrop===c.name ? 'background:#1f6d3c' : 'background:#eceae3'">
          <span class="text-[10px] md:text-[11px] 2xl:text-[12px] font-semibold text-center block" :style="selectedCrop===c.name ? 'color:#fff' : 'color:#3c433d'" x-text="c.name"></span>
        </div>
      </button>
    </template>
  </div>
</div>
