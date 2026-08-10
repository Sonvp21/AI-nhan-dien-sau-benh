{{-- ================= CHỌN MÔ HÌNH CÂY: box riêng, nằm trên cùng cột chụp ảnh =================
     Danh sách cây đã nhiều hơn 5 nên chuyển sang dạng slider cuộn ngang, có
     nút mũi tên 2 bên (chỉ hiện khi rê chuột vào khu vực chọn, ẩn mặc định
     để đỡ tốn chỗ). Trên mobile cuộn bằng ngón tay như bình thường.
     Responsive: mobile (mặc định) / HD (md:) / FHD (2xl:) --}}
<div class="rounded-xl p-4 md:p-5 2xl:p-6" style="background:#fff;border:1px solid #eceae3">
  <p class="text-[17px] md:text-[18px] 2xl:text-[20px] font-bold mb-3" style="color:#12341d">Chọn mô hình cây trồng</p>

  <div class="relative group/crops">
    <button @click="scrollCrops(-1)" class="hidden md:flex opacity-0 group-hover/crops:opacity-100 transition absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 z-10 w-7 h-7 2xl:w-8 2xl:h-8 rounded-full items-center justify-center shadow" style="background:#fff;border:1px solid #dbe8d2;color:#1f6d3c">
      <i data-lucide="chevron-left" class="w-4 h-4"></i>
    </button>

    <div x-ref="cropScroll" class="flex gap-2 md:gap-3 2xl:gap-4 overflow-x-auto no-scrollbar scroll-smooth">
      <template x-for="c in crops" :key="c.name">
        <button @click="selectCrop(c.name)" class="rounded-xl overflow-hidden transition shrink-0 w-[76px] md:w-[92px] 2xl:w-[108px]" :class="selectedCrop===c.name ? '' : 'opacity-55'"
                :style="selectedCrop===c.name ? 'border:2px solid #1f6d3c' : 'border:2px solid transparent'">
          <!-- vùng ảnh: ảnh đại diện từng cây (thu nhỏ lại) -->
          <div class="h-9 md:h-11 2xl:h-12 overflow-hidden flex items-center justify-center" style="background:#f2f7ee">
            <template x-if="c.img">
              <img :src="c.img" :alt="c.name" class="w-full h-full object-cover">
            </template>
            <template x-if="!c.img">
              <i :data-lucide="c.icon" class="w-5 h-5 md:w-6 md:h-6" style="color:#1f6d3c"></i>
            </template>
          </div>
          <!-- vùng chữ: tách riêng khỏi ảnh, chữ to hơn -->
          <div class="py-1.5" :style="selectedCrop===c.name ? 'background:#1f6d3c' : 'background:#eceae3'">
            <span class="text-[12.5px] md:text-[14px] 2xl:text-[15.5px] font-semibold text-center block" :style="selectedCrop===c.name ? 'color:#fff' : 'color:#3c433d'" x-text="c.name"></span>
          </div>
        </button>
      </template>
    </div>

    <button @click="scrollCrops(1)" class="hidden md:flex opacity-0 group-hover/crops:opacity-100 transition absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 z-10 w-7 h-7 2xl:w-8 2xl:h-8 rounded-full items-center justify-center shadow" style="background:#fff;border:1px solid #dbe8d2;color:#1f6d3c">
      <i data-lucide="chevron-right" class="w-4 h-4"></i>
    </button>
  </div>
</div>
