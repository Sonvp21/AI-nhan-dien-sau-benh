"""
Train model phân loại bệnh lá sắn - Cassava Leaf Disease Classification
5 lớp: bacterial_blight, brown_streak_disease, green_mottle, healthy, mosaic_disease
LƯU Ý: dataset mất cân bằng nghiêm trọng (mosaic_disease 13158 ảnh vs bacterial_blight chỉ 1087 ảnh,
gấp ~12 lần) -> dùng class weighting trong loss function để tránh model thiên vị lớp nhiều ảnh
"""

import os
import numpy as np
import torch
import torch.nn as nn
from torchvision import datasets, transforms, models
from torch.utils.data import DataLoader
from sklearn.metrics import classification_report, confusion_matrix
from sklearn.utils.class_weight import compute_class_weight
import splitfolders

SOURCE_DIR = "cassava_data_clean"
SPLIT_DIR = "cassava_split"

if not os.path.exists(SPLIT_DIR):
    print("Đang chia dataset...")
    splitfolders.ratio(SOURCE_DIR, output=SPLIT_DIR, seed=42, ratio=(.7, .15, .15))
else:
    print("Dataset đã được chia sẵn, bỏ qua bước chia.")

train_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.RandomHorizontalFlip(),
    transforms.RandomRotation(15),
    transforms.ColorJitter(brightness=0.15, contrast=0.15),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])

eval_transform = transforms.Compose([
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])

train_ds = datasets.ImageFolder(f"{SPLIT_DIR}/train", transform=train_transform)
val_ds   = datasets.ImageFolder(f"{SPLIT_DIR}/val", transform=eval_transform)
test_ds  = datasets.ImageFolder(f"{SPLIT_DIR}/test", transform=eval_transform)

BATCH_SIZE = 32
train_loader = DataLoader(train_ds, batch_size=BATCH_SIZE, shuffle=True)
val_loader   = DataLoader(val_ds, batch_size=BATCH_SIZE)
test_loader  = DataLoader(test_ds, batch_size=BATCH_SIZE)

num_classes = len(train_ds.classes)
print(f"\nSố lớp: {num_classes}")
print(f"Tên lớp: {train_ds.classes}")
print(f"Số ảnh train: {len(train_ds)} | val: {len(val_ds)} | test: {len(test_ds)}\n")

# ==== TÍNH CLASS WEIGHTS để xử lý mất cân bằng ====
train_labels = [label for _, label in train_ds.samples]
class_weights = compute_class_weight('balanced', classes=np.unique(train_labels), y=train_labels)
class_weights = torch.tensor(class_weights, dtype=torch.float)
print("Class weights (lớp ít ảnh sẽ có trọng số cao hơn):")
for cls, w in zip(train_ds.classes, class_weights):
    print(f"  {cls}: {w:.3f}")
print()

device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
print(f"Đang chạy trên: {device}\n")
class_weights = class_weights.to(device)

model = models.efficientnet_b0(weights="IMAGENET1K_V1")

total_blocks = len(model.features)
freeze_until = int(total_blocks * 0.7)
for i, layer in enumerate(model.features):
    if i < freeze_until:
        for param in layer.parameters():
            param.requires_grad = False

model.classifier[1] = nn.Linear(model.classifier[1].in_features, num_classes)
model = model.to(device)

criterion = nn.CrossEntropyLoss(weight=class_weights)  # <-- điểm khác biệt so với 3 cây trước
trainable_params = [p for p in model.parameters() if p.requires_grad]
optimizer = torch.optim.Adam(trainable_params, lr=5e-4)
scheduler = torch.optim.lr_scheduler.StepLR(optimizer, step_size=7, gamma=0.5)

EPOCHS = 15
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

    scheduler.step()
    train_acc = correct / len(train_ds)

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
        torch.save(model.state_dict(), "best_cassava_model.pth")
        print(f"  -> Đã lưu model tốt nhất (Val Acc: {val_acc:.3f})")

print(f"\nHoàn tất training. Val Acc tốt nhất: {best_val_acc:.3f}")

model.load_state_dict(torch.load("best_cassava_model.pth"))
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
print("Lưu ý: với dữ liệu mất cân bằng, xem kỹ recall từng lớp (đặc biệt bacterial_blight - lớp ít ảnh nhất)")
print("thay vì chỉ nhìn accuracy tổng thể, vì accuracy dễ bị 'lừa' bởi lớp mosaic_disease chiếm đa số")
print(classification_report(truths, preds, target_names=train_ds.classes))

print("=== CONFUSION MATRIX ===")
print("Hàng = nhãn thật, Cột = nhãn dự đoán")
print(train_ds.classes)
print(confusion_matrix(truths, preds))
