function agriApp() {
  return {
    tab: 'home',
    selectedCrop: 'Lúa',
    crops: [
      { name: 'Lúa', emoji: '🌾' },
      { name: 'Sắn', emoji: '🥔' },
      { name: 'Chè', emoji: '🍵' },
      { name: 'Ngô', emoji: '🌽' },
    ],
    scanState: 'idle',
    scanProgress: 0,
    scanLabel: 'Đang phân tích ảnh...',
    startScan() {
      this.tab = 'scan';
      this.scanState = 'idle';
      this.scanProgress = 0;
    },
    runScan() {
      const labels = [
        'Đang phân tích ảnh...',
        'Nhận diện vùng tổn thương...',
        'Đối chiếu mô hình AI...',
        'Hoàn tất chẩn đoán',
      ];
      this.scanLabel = labels[0];
      const interval = setInterval(() => {
        this.scanProgress += 4;
        const idx = Math.min(Math.floor(this.scanProgress / 26), labels.length - 1);
        this.scanLabel = labels[idx];
        if (this.scanProgress >= 100) {
          clearInterval(interval);
          setTimeout(() => {
            this.tab = 'result';
            this.scanState = 'idle';
          }, 400);
        }
      }, 70);
    },
  };
}
