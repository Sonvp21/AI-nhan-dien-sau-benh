"""
Train model phân loại bệnh lá chè - Tea Sickness Dataset
8 lớp: algal leaf, Anthracnose, bird eye spot, brown blight, gray light, healthy, red leaf spot, white spot
Dataset nhỏ (~885 ảnh) -> dùng Transfer Learning + Augmentation mạnh để tránh overfit
"""

import os
import torch
import torch.nn as nn
from torchvision import datasets, transforms, models
from torch.utils.data import DataLoader
from sklearn.metrics import classification_report, confusion_matrix
import splitfolders

# ==== BƯỚC 0: CHIA TRAIN/VAL/TEST (chỉ cần chạy 1 lần) ====
SOURCE_DIR = "tea_data/tea sickness dataset"
SPLIT_DIR = "tea_split"

if not os.path.exists(SPLIT_DIR):
    print("Đang chia dataset...")
    splitfolders.ratio(SOURCE_DIR, output=SPLIT_DIR, seed=42, ratio=(.7, .15, .15))
else:
    print("Dataset đã được chia sẵn, bỏ qua bước chia.")

# ==== BƯỚC 1: AUGMENTATION MẠNH (vì dataset nhỏ, cần tăng cường dữ liệu) ====
train_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.RandomHorizontalFlip(),
    transforms.RandomVerticalFlip(),
    transforms.RandomRotation(25),
    transforms.ColorJitter(brightness=0.2, contrast=0.2, saturation=0.2),
    transforms.RandomAffine(degrees=0, translate=(0.1, 0.1)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])

eval_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])

# ==== BƯỚC 2: LOAD DỮ LIỆU ====
train_ds = datasets.ImageFolder(f"{SPLIT_DIR}/train", transform=train_transform)
val_ds   = datasets.ImageFolder(f"{SPLIT_DIR}/val", transform=eval_transform)
test_ds  = datasets.ImageFolder(f"{SPLIT_DIR}/test", transform=eval_transform)

BATCH_SIZE = 16  # nhỏ vì dataset ít ảnh, batch lớn dễ gây overfit nhanh
train_loader = DataLoader(train_ds, batch_size=BATCH_SIZE, shuffle=True)
val_loader   = DataLoader(val_ds, batch_size=BATCH_SIZE)
test_loader  = DataLoader(test_ds, batch_size=BATCH_SIZE)

num_classes = len(train_ds.classes)
print(f"\nSố lớp: {num_classes}")
print(f"Tên lớp: {train_ds.classes}")
print(f"Số ảnh train: {len(train_ds)} | val: {len(val_ds)} | test: {len(test_ds)}\n")

# ==== BƯỚC 3: DEVICE (tự nhận GPU nếu có, không thì dùng CPU) ====
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
print(f"Đang chạy trên: {device}\n")

# ==== BƯỚC 4: MODEL - Transfer Learning với EfficientNet-B0 ====
model = models.efficientnet_b0(weights="IMAGENET1K_V1")

# Đóng băng phần lớn các lớp trích xuất đặc trưng (feature extractor)
# vì dataset nhỏ, chỉ nên fine-tune vài lớp cuối để tránh overfit
for param in model.features.parameters():
    param.requires_grad = False

# Thay lớp classifier cuối cho đúng 8 lớp bệnh
model.classifier[1] = nn.Linear(model.classifier[1].in_features, num_classes)
model = model.to(device)

criterion = nn.CrossEntropyLoss()
# Chỉ optimize phần classifier (vì phần features đã đóng băng)
optimizer = torch.optim.Adam(model.classifier.parameters(), lr=1e-3)

# ==== BƯỚC 5: TRAINING LOOP ====
EPOCHS = 20
best_val_acc = 0.0

for epoch in range(EPOCHS):
    model.train()
    total_loss = 0
    correct = 0
    for imgs, labels in train_loader:
        imgs, labels = imgs.to(device), labels.to(device)
        optimizer.zero_grad()
        out = model(imgs)
        loss = criterion(out, labels)
        loss.backward()
        optimizer.step()
        total_loss += loss.item()
        correct += (out.argmax(1) == labels).sum().item()

    train_acc = correct / len(train_ds)

    # Validation
    model.eval()
    val_correct = 0
    with torch.no_grad():
        for imgs, labels in val_loader:
            imgs, labels = imgs.to(device), labels.to(device)
            out = model(imgs)
            val_correct += (out.argmax(1) == labels).sum().item()
    val_acc = val_correct / len(val_ds)

    print(f"Epoch {epoch+1:2d}/{EPOCHS} | Loss: {total_loss/len(train_loader):.4f} | "
          f"Train Acc: {train_acc:.3f} | Val Acc: {val_acc:.3f}")

    if val_acc > best_val_acc:
        best_val_acc = val_acc
        torch.save(model.state_dict(), "best_tea_model.pth")
        print(f"  -> Đã lưu model tốt nhất (Val Acc: {val_acc:.3f})")

print(f"\nHoàn tất training. Val Acc tốt nhất: {best_val_acc:.3f}")

# ==== BƯỚC 6: ĐÁNH GIÁ TRÊN TẬP TEST (dùng model tốt nhất) ====
model.load_state_dict(torch.load("best_tea_model.pth"))
model.eval()

preds, truths = [], []
with torch.no_grad():
    for imgs, labels in test_loader:
        imgs = imgs.to(device)
        out = model(imgs)
        pred = out.argmax(1).cpu().numpy()
        preds.extend(pred)
        truths.extend(labels.numpy())

print("\n=== KẾT QUẢ TRÊN TẬP TEST ===")
print(classification_report(truths, preds, target_names=train_ds.classes))

print("=== CONFUSION MATRIX ===")
print("Hàng = nhãn thật, Cột = nhãn dự đoán")
print(train_ds.classes)
print(confusion_matrix(truths, preds))