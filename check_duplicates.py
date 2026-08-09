"""
Kiểm tra ảnh trùng lặp (hoặc gần trùng) giữa train/val/test
Nếu tìm thấy trùng lặp -> giải thích vì sao accuracy 100% không đáng tin
"""
import os
import hashlib
from collections import defaultdict

SPLIT_DIR = "rice_split"

def file_hash(path):
    with open(path, "rb") as f:
        return hashlib.md5(f.read()).hexdigest()

hash_to_files = defaultdict(list)

for split in ["train", "val", "test"]:
    split_path = os.path.join(SPLIT_DIR, split)
    for cls in os.listdir(split_path):
        cls_path = os.path.join(split_path, cls)
        for fname in os.listdir(cls_path):
            fpath = os.path.join(cls_path, fname)
            h = file_hash(fpath)
            hash_to_files[h].append((split, cls, fname))

duplicates = {h: files for h, files in hash_to_files.items() if len(files) > 1}

print(f"Tổng số ảnh đã kiểm tra: {sum(len(v) for v in hash_to_files.values())}")
print(f"Số nhóm ảnh trùng lặp HOÀN TOÀN (byte-for-byte): {len(duplicates)}")

cross_split_dupes = 0
for h, files in duplicates.items():
    splits = set(f[0] for f in files)
    if len(splits) > 1:
        cross_split_dupes += 1

print(f"Số nhóm trùng lặp XUYÊN QUA train/val/test (nguy hiểm nhất): {cross_split_dupes}")

if cross_split_dupes > 0:
    print("\n⚠️  PHÁT HIỆN DATA LEAKAGE: có ảnh giống hệt nhau xuất hiện ở cả tập train và test.")
    print("Đây là nguyên nhân khiến accuracy cao bất thường (100%) - model đã 'nhìn thấy' ảnh test trong lúc train.")
    print("\nVí dụ 5 nhóm trùng đầu tiên:")
    count = 0
    for h, files in duplicates.items():
        splits = set(f[0] for f in files)
        if len(splits) > 1:
            print(f"  {files}")
            count += 1
            if count >= 5:
                break
else:
    print("\n✅ Không phát hiện ảnh trùng byte-for-byte xuyên qua các tập.")
    print("Accuracy 100% có thể do dataset quá dễ phân biệt giữa 4 lớp bệnh, hoặc do ảnh RẤT giống nhau nhưng không trùng byte tuyệt đối (cần kiểm tra thêm bằng perceptual hash nếu vẫn nghi ngờ).")
