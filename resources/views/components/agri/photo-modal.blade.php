{{-- ================= MODAL: chụp ảnh / chọn ảnh từ thư viện ================= --}}
<div x-show="dropzoneModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(18,52,29,.45)" @click.self="closeModal()">
  <div class="bg-white rounded-2xl w-full max-w-sm 2xl:max-w-md p-6 2xl:p-7 relative shadow-xl">
    <button @click="closeModal()" class="absolute top-3.5 right-3.5 w-7 h-7 rounded-full flex items-center justify-center" style="background:#f2f7ee;color:#1f6d3c"><i data-lucide="x" class="w-4 h-4"></i></button>

    <!-- bước chọn nguồn ảnh -->
    <template x-if="modalStep==='choose'">
      <div>
        <p class="text-[16px] 2xl:text-[17px] font-bold mb-4" style="color:#12341d">Thêm ảnh cây trồng</p>
        <div class="flex flex-col gap-3">
          <button @click="$refs.cameraInput.click()" class="flex items-center gap-3 px-4 py-3.5 rounded-xl text-left transition hover:opacity-85" style="background:#f2f7ee;border:1px solid #dbe8d2">
            <span class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background:#e2efd9;color:#1f6d3c"><i data-lucide="camera" class="w-[18px] h-[18px]"></i></span>
            <span class="text-[14px] 2xl:text-[15px] font-semibold" style="color:#12341d">Chụp ảnh</span>
          </button>
          <button @click="$refs.galleryInput.click()" class="flex items-center gap-3 px-4 py-3.5 rounded-xl text-left transition hover:opacity-85" style="background:#fbf1ea;border:1px solid #f0d9c4">
            <span class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background:#f6e2d1;color:#c1440e"><i data-lucide="image" class="w-[18px] h-[18px]"></i></span>
            <span class="text-[14px] 2xl:text-[15px] font-semibold" style="color:#12341d">Chọn ảnh từ thư viện</span>
          </button>
        </div>
      </div>
    </template>

    <!-- bước xem lại ảnh vừa chọn / chụp -->
    <template x-if="modalStep==='preview'">
      <div>
        <p class="text-[16px] 2xl:text-[17px] font-bold mb-4" style="color:#12341d">Xem lại ảnh</p>
        <div class="grid grid-cols-3 gap-2 mb-5">
          <template x-for="(p, i) in pendingFiles" :key="i">
            <div class="relative aspect-square rounded-lg overflow-hidden">
              <img :src="p.url" class="w-full h-full object-cover">
              <button @click="removePendingPhoto(i)" class="absolute top-1 right-1 w-5 h-5 rounded-full flex items-center justify-center text-white" style="background:rgba(18,52,29,.7)"><i data-lucide="x" class="w-3 h-3"></i></button>
            </div>
          </template>
          <button @click="modalStep='choose'" class="aspect-square rounded-lg flex items-center justify-center" style="border:2px dashed #dbe8d2;color:#8a8f83"><i data-lucide="plus" class="w-5 h-5"></i></button>
        </div>
        <div class="flex gap-3">
          <button @click="cancelPhotos()" class="flex-1 px-4 py-2.5 rounded-lg text-[13.5px] font-semibold" style="border:1px solid #c1440e;color:#c1440e">Hủy</button>
          <button @click="confirmPhotos()" class="flex-1 px-4 py-2.5 rounded-lg text-[13.5px] font-semibold text-white" style="background:#1f6d3c">Xác nhận</button>
        </div>
      </div>
    </template>
  </div>

  <input x-ref="cameraInput" type="file" accept="image/*" capture="environment" class="hidden" @change="handleFileChosen($event)">
  <input x-ref="galleryInput" type="file" accept="image/*" multiple class="hidden" @change="handleFileChosen($event)">
</div>
