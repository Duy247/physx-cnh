# Đóng góp tài liệu PDF

Phạm vi của cộng tác viên rất rõ ràng: thêm PDF, đăng ký tài liệu vào danh mục, đẩy branch lên fork và mở Pull Request (PR) vào `Duy247/physx-cnh:master`. Chủ repository sẽ xem xét và merge.

Không sửa PHP, CSS, JavaScript, cấu hình hosting hoặc các PDF đã có trong một PR đóng góp tài liệu.

## 1. Yêu cầu

- Git.
- Một tài khoản GitHub và một fork của `Duy247/physx-cnh`.
- PHP 8.1 trở lên nếu muốn dùng công cụ đăng ký tự động. Nếu không có PHP, có thể sửa JSON thủ công.
- PDF phải nhỏ hơn hoặc bằng 95 MiB, mở được, không đặt mật khẩu và có nguồn/phân phối phù hợp.

## 2. Clone nhẹ, không tải toàn bộ kho PDF cũ

Repository có lịch sử PDF lớn. Dùng partial clone và sparse checkout:

```bash
git clone --filter=blob:none --no-checkout https://github.com/<username>/physx-cnh.git
cd physx-cnh
git sparse-checkout init --cone
git sparse-checkout set config physics/catalog physics/library schema src tools tests .github
git checkout master
git switch -c docs/<ten-tai-lieu>
```

Thay `<username>` bằng tài khoản GitHub của bạn và `<ten-tai-lieu>` bằng tên branch ngắn, viết thường, không dấu.

## 3. Chọn danh mục

```bash
php tools/catalog.php catalogs
```

Các mã danh mục hiện có:

- `books-pre-vpho`
- `books-vpho-vn`
- `books-vpho-en`
- `materials-pho`
- `paper-sol-pho`
- `magazines`
- `lessons`

## 4. Thêm và đăng ký PDF

Khuyến nghị dùng công cụ:

```bash
php tools/catalog.php add \
  --catalog materials-pho \
  --pdf "/duong-dan/toi/tai-lieu.pdf" \
  --title "Tên tài liệu" \
  --author "Tên tác giả" \
  --description "Mô tả ngắn" \
  --source "Nguồn tài liệu hoặc đường dẫn nguồn"
```

Trên PowerShell, dùng dấu backtick thay cho `\` để xuống dòng, hoặc viết toàn bộ lệnh trên một dòng.

Công cụ sẽ:

1. Kiểm tra định dạng và kích thước PDF.
2. Chuẩn hoá tên file.
3. Chép file vào `physics/library/<catalog>/`.
4. Thêm bản ghi vào `physics/catalog/<catalog>.json`.
5. Kiểm tra lại manifest và in hai file cần commit.

### Sửa JSON thủ công

Nếu không có PHP, hãy:

1. Chép PDF vào `physics/library/<catalog>/ten-file.pdf`.
2. Mở `physics/catalog/<catalog>.json`.
3. Thêm một object cuối mảng `items`:

```json
{
  "title": "Tên tài liệu",
  "author": "Tên tác giả",
  "file": "library/materials-pho/ten-file.pdf",
  "description": "Mô tả ngắn",
  "source": "Nguồn tài liệu"
}
```

Metadata chỉ dùng văn bản thuần, không chèn HTML. Đường dẫn `file` tương đối so với thư mục `physics/` và phân biệt chữ hoa/chữ thường.

## 5. Kiểm tra

Với sparse checkout:

```bash
php tools/catalog.php validate --git-tree=HEAD
```

Với clone đầy đủ:

```bash
php tools/catalog.php validate
```

GitHub sẽ chạy lại toàn bộ kiểm tra khi PR được mở. PR chỉ được merge khi kiểm tra thành công và chủ repository chấp thuận.

## 6. Commit, push và mở PR

```bash
git status
git add physics/library/<catalog>/<ten-file>.pdf physics/catalog/<catalog>.json
git commit -m "Add <ten-tai-lieu>"
git push -u origin docs/<ten-tai-lieu>
```

Sau đó mở GitHub và tạo PR:

- Source: branch trong fork của bạn.
- Target: `Duy247/physx-cnh:master`.
- Một PR chỉ nên chứa một tài liệu hoặc một nhóm tài liệu liên quan.
- Điền đầy đủ checklist về nguồn và quyền phân phối.

Không push trực tiếp vào repository chính và không tự deploy. Sau khi PR được merge, Hostinger sẽ đồng bộ từ `master`.
