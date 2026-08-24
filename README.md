# PhysX-CNH - Kho nguồn tài liệu
Đây là repository của trang web [PhysX-CNH](https://physx-cnh.com)

Xem wiki tại [đây](https://github.com/Duy247/physx-cnh/wiki)

Repository này là nguồn chuẩn cho giao diện PHP, manifest và toàn bộ kho PDF của PhysX-CNH. Hostinger phục vụ trực tiếp website, ảnh bìa, thư viện trình duyệt và tài liệu; production không phụ thuộc Vercel hay Blob.

## Mục tiêu

Trang web được tạo ra nhằm mục đích hỗ trợ các học sinh khối chuyên Vật lý trường THPT Chuyên Nguyễn Huệ nói riêng và toàn bộ học sinh có niềm đam mê với môn Vật lý nói chung. 

Đây sẽ là một công cụ học tập hữu dụng giúp cho học sinh có thể truy cập những tài liệu hỗ trợ học và ôn thi, hướng tới các kỳ thi Học sinh giỏi hoặc Olympic Vật lý cấp khu vực và cả quốc tế.

## Kỹ thuật

Manifest JSON trong `physics/catalog/` là nguồn chuẩn. `public-snapshot.json` chứa đúng 325 tài liệu đã biên mục; `inventory.json` theo dõi 1.366 PDF nhưng không đưa 1.047 tệp nháp lên thư viện. PHP 8.1+ đọc snapshot tại chỗ và Hostinger là origin duy nhất.

```bash
npm install
npm run catalog:inventory
npm run graphs:generate
python tools/generate-pdf-covers.py
```

## Tính năng

Website cung cấp hệ hành tinh Three.js, tìm kiếm và lọc metadata, ảnh bìa nhẹ, sơ đồ học tập GoJS và trình đọc PDF.js tiêu chuẩn. Trang đọc không lưu ghi chú hay tiến độ.

## Đóng góp

Đóng góp tài liệu được thực hiện qua fork và Pull Request vào branch `master`. Cộng tác viên chỉ cần thêm PDF, đăng ký tài liệu, push branch lên fork; chủ repository sẽ review và merge.

Xem quy trình đầy đủ tại [CONTRIBUTING.md](CONTRIBUTING.md), hoặc bắt đầu bằng cách [fork repository](https://github.com/Duy247/physx-cnh/fork).
