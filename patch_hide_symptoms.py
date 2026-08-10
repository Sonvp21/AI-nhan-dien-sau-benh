path = "resources/views/components/agri/photo-panel.blade.php"
with open(path, encoding="utf-8") as f:
    content = f.read()

old = '<div x-show="diagnosed" x-cloak x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="mt-6">'
new = '<div x-show="diagnosed && symptomPages.length" x-cloak x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="mt-6">'

count = content.count(old)
if count != 1:
    raise SystemExit(f"LỖI: xuất hiện {count} lần (cần đúng 1). Dừng lại.")

content = content.replace(old, new)
with open(path, "w", encoding="utf-8") as f:
    f.write(content)
print("Đã ẩn khối Triệu chứng khác khi không có dữ liệu")
