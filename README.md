# Dokumentasi API

## Gambaran Umum

API ini menyediakan fitur untuk mengelola pengguna (user), kategori (kategori), upah (upah), produk (barang), dan stok barang (stock). Role yang didukung meliputi Admin dan Staff, di mana masing-masing role memiliki hak akses tertentu.

---

## Autentikasi

Semua rute API dilindungi oleh middleware autentikasi Sanctum. Gunakan endpoint berikut untuk autentikasi:

### Login

**Metode**: `POST`

**Endpoint**: `/login`

**Payload**:

```json
{
    "email": "user@example.com",
    "password": "password"
}
```

**Respons**:

- `200`: Token autentikasi berhasil diberikan.
- `401`: Akses tidak sah.

---

## Manajemen Pengguna

### Buat Karyawan (Staff)

**Metode**: `POST`

**Endpoint**: `/staff/users/register/karyawan`

**Payload**:

```json
{
    "nama_lengkap": "John Doe",
    "email": "johndoe@example.com"
}
```

**Respons**:

- `201`: Pengguna berhasil dibuat.
- `422`: Kesalahan validasi.

### Buat Pengguna (Admin)

**Metode**: `POST`

**Endpoint**: `/admin/users`

**Payload**:

```json
{
    "nama_lengkap": "Jane Doe",
    "email": "janedoe@example.com",
    "role": "Staff"
}
```

**Respons**:

- `201`: Pengguna berhasil dibuat.
- `403`: Akses tidak sah.
- `422`: Kesalahan validasi.

---

## Manajemen Kategori

### Lihat Semua Kategori

**Metode**: `GET`

**Endpoint**: 
- `/staff/kategori` (Staff) 
- `/admin/kategori` (Admin)

**Respons**:

- `200`: Daftar kategori berhasil diambil.

### Lihat Detail Kategori

**Metode**: `GET`

**Endpoint**: 
- `/staff/kategori/{id}` (Staff) 
- `/admin/kategori/{id}` (Admin)

**Respons**:

- `200`: Detail kategori berhasil diambil.
- `404`: Kategori tidak ditemukan.

### Tambah Kategori

**Metode**: `POST`

**Endpoint**: 
- `/staff/kategori` (Staff) 
- `/admin/kategori` (Admin)

**Payload**:

```json
{
    "nama": "Nama Kategori",
    "deskripsi": "Deskripsi Kategori"
}
```

**Respons**:

- `201`: Kategori berhasil ditambahkan.
- `422`: Kesalahan validasi.

### Perbarui Kategori (Admin)

**Metode**: `PUT`

**Endpoint**: `/admin/kategori/{id}`

**Payload**:

```json
{
    "nama": "Nama Baru",
    "deskripsi": "Deskripsi Baru"
}
```

**Respons**:

- `200`: Kategori berhasil diperbarui.
- `404`: Kategori tidak ditemukan.

### Hapus Kategori (Admin)

**Metode**: `DELETE`

**Endpoint**: `/admin/kategori/{id}`

**Respons**:

- `200`: Kategori berhasil dihapus.
- `404`: Kategori tidak ditemukan.

---

## Manajemen Produk

### Lihat Semua Produk

**Metode**: `GET`

**Endpoint**: 
- `/staff/barang` (Staff) 
- `/admin/barang` (Admin)

**Respons**:

- `200`: Daftar produk berhasil diambil.

### Tambah Produk

**Metode**: `POST`

**Endpoint**: 
- `/staff/barang` (Staff) 
- `/admin/barang` (Admin)

**Payload**:

```json
{
    "nama": "Nama Produk",
    "deskripsi": "Deskripsi Produk",
    "kategori_barang": 1,
    "stok_awal": 100,
    "stok_tersedia": 100,
    "upah": 500
}
```

**Respons**:

- `201`: Produk berhasil ditambahkan.
- `422`: Kesalahan validasi.

### Tambah Stok Produk

**Metode**: `POST`

**Endpoint**: `/staff/stock/{id}` (Staff) atau `/admin/stock/{id}` (Admin)

**Payload**:

```json
{
    "stock": 50
}
```

**Respons**:

- `200`: Stok berhasil ditambahkan.
- `404`: Produk tidak ditemukan.
- `422`: Kesalahan validasi.

---

## Manajemen Upah

### Tambah Upah

**Metode**: `POST`

**Endpoint**: 
- `/staff/upah` (Staff) 
- `/admin/upah` (Admin)

**Payload**:

```json
{
    "karyawan_id": 1,
    "periode_mulai": "2025-01-01"
}
```

**Respons**:

- `201`: Upah berhasil ditambahkan.
- `422`: Kesalahan validasi.

---

## Kode Kesalahan

- `403`: Akses tidak sah.
- `404`: Sumber daya tidak ditemukan.
- `422`: Kesalahan validasi.
- `500`: Kesalahan server.

---
