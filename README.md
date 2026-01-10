# 🚀 Berkah Production

Production management system untuk konveksi Berkah. Kelola orders, work orders, customers, dan payment tracking dengan mudah.

## ✨ Features

- 📦 **Order Management** - Kelola pesanan customer dengan detail lengkap
- 🏭 **Work Order System** - Track progress produksi dari cutting, printing, sewing, sampai packing
- 👥 **Customer Management** - Database customer dengan riwayat order
- 💰 **Payment Tracking** - Monitor pembayaran dan piutang
- 📊 **Dashboard Analytics** - Visualisasi data dengan charts (ApexCharts)
- 📄 **PDF Generation** - Export work orders ke PDF dengan DomPDF
- 🖼️ **Multi-Image Upload** - Upload 7 gambar sekaligus di work orders
- 🎨 **Modern UI** - Tailwind CSS 4.x dengan Alpine.js & Turbo (Hotwire)

## 🚀 Quick Deployment (Automated Scripts)

Deploy aplikasi ke VPS dalam **~20 menit** dengan 3 script otomatis:

### 1️⃣ Setup VPS (~10 menit)
```bash
scp scripts/vps-setup.sh root@YOUR_VPS_IP:/root/
ssh root@YOUR_VPS_IP
bash vps-setup.sh
```

### 2️⃣ Deploy Laravel (~5 menit)
```bash
ssh deployuser@YOUR_VPS_IP
bash deploy-laravel.sh
```

### 3️⃣ Update Application (~2 menit)
```bash
bash update.sh
```

📚 **Dokumentasi lengkap:** [SCRIPTS-USAGE.md](SCRIPTS-USAGE.md)

## 📖 Manual Deployment

Kalau prefer manual deployment, ikuti: [DEPLOYMENT-GUIDE.md](DEPLOYMENT-GUIDE.md)

## 🛠️ Tech Stack

- **Backend:** Laravel 12.0 + PHP 8.2
- **Frontend:** Tailwind CSS 4.x + Alpine.js 3.15 + Turbo 8.0
- **Database:** MySQL 8.0
- **Charts:** ApexCharts 5.3
- **PDF:** DomPDF 3.1
- **Build:** Vite 7.x

## 📋 Requirements

- PHP 8.2+
- MySQL 8.0+
- Node.js 20.x LTS
- Composer 2.x
- Nginx / Apache

## 💻 Local Development

```bash
# Clone repository
git clone https://github.com/girixxz/berkah-production.git
cd berkah-production

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database di .env
# DB_DATABASE=berkah_production
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# Run migrations & seeders
php artisan migrate
php artisan db:seed

# Build assets
npm run dev

# Start server
php artisan serve
```

Access: `http://localhost:8000`

**Default Login:**
- Username: `admin`
- Password: `password`

## 🎯 User Roles

| Role | Access |
|------|--------|
| **Owner** | Full access semua fitur |
| **Admin** | Manage orders, work orders, customers |
| **PM (Project Manager)** | View & update work orders |
| **Employee** | View work orders assigned |

## 📱 Responsive Design

Optimized untuk:
- 🖥️ Desktop (1920px+)
- 💻 Laptop (1366px - 1920px)
- 📱 iPad (768px - 1024px)
- 📱 Mobile (375px - 768px)

## 🐛 Troubleshooting

### Work Order Upload Failed
```bash
# Check PHP limits
php -i | grep upload_max_filesize  # Should be: 50M
php -i | grep post_max_size        # Should be: 60M

# Check storage permissions
sudo chmod -R 775 storage/
sudo chown -R www-data:www-data storage/
```

### PDF Download Error
```bash
# Check ImageMagick limits
convert -list resource

# Should show:
# Memory: 512MiB
# Map: 1GiB
# Disk: 1GiB
```

More troubleshooting: [SCRIPTS-USAGE.md](SCRIPTS-USAGE.md#troubleshooting)

## 📞 Support

- 📄 Documentation: [DEPLOYMENT-GUIDE.md](DEPLOYMENT-GUIDE.md)
- 🚀 Scripts Guide: [SCRIPTS-USAGE.md](SCRIPTS-USAGE.md)
- 📝 TODO List: [TODO.md](TODO.md)

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
