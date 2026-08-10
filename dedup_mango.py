"""
Loại bỏ ảnh trùng lặp (byte-for-byte) khỏi mango_data TRƯỚC khi chia tập.
Chỉ giữ lại 1 ảnh đại diện cho mỗi nhóm trùng lặp -> tránh leakage khi chia train/val/test.
"""
import os
import hashlib
import shutil
from collections import defaultdict

SOURCE_DIR = "mango_data"
CLEAN_DIR = "mango_data_clean"

def file_hash(path):
    with open(path, "rb") as f:
        return hashlib.md5(f.read()).hexdigest()

hash_seen = set()
kept, removed = 0, 0

if os.path.exists(CLEAN_DIR):
    shutil.rmtree(CLEAN_DIR)

for cls in os.listdir(SOURCE_DIR):
    cls_path = os.path.join(SOURCE_DIR, cls)
    if not os.path.isdir(cls_path):
        continue
    clean_cls_path = os.path.join(CLEAN_DIR, cls)
    os.makedirs(clean_cls_path, exist_ok=True)

    for fname in os.listdir(cls_path):
        fpath = os.path.join(cls_path, fname)
        h = file_hash(fpath)
        if h in hash_seen:
            removed += 1
            continue
        hash_seen.add(h)
        shutil.copy2(fpath, os.path.join(clean_cls_path, fname))
        kept += 1

print(f"Đã giữ lại: {kept} ảnh (duy nhất)")
print(f"Đã loại bỏ: {removed} ảnh (trùng lặp)")
print(f"\nDataset sạch đã lưu tại: {CLEAN_DIR}/")
print("Bước tiếp theo: xóa rice_split cũ, sửa SOURCE_DIR trong train_rice.py thành 'mango_data_clean', rồi train lại.")
