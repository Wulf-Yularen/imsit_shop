# ИМСИТ Shop — Интернет-магазин канцтоваров
## НАН ЧОУ ВО Академия ИМСИТ

---

## 📁 Структура проекта

```
imsit_shop/
├── index.php              # Главная страница
├── catalog.php            # Каталог товаров
├── product.php            # Страница товара
├── cart.php               # Корзина
├── checkout.php           # Оформление заказа
├── order-success.php      # Успешный заказ
├── order.php              # Детали заказа (для пользователя)
├── login.php              # Вход
├── register.php           # Регистрация
├── account.php            # Личный кабинет
├── logout.php             # Выход
├── .htaccess              # Настройки Apache
├── database.sql           # SQL для создания БД
│
├── includes/
│   ├── config.php         # Настройки БД и приложения
│   └── functions.php      # Функции (авторизация, корзина и т.д.)
│
├── public/
│   ├── css/style.css      # Главный CSS
│   ├── js/main.js         # JavaScript
│   └── images/            # Изображения товаров
│
├── api/
│   └── cart.php           # AJAX API корзины
│
└── admin/
    ├── index.php          # Дашборд
    ├── orders.php         # Управление заказами
    ├── order-detail.php   # Детали заказа
    ├── products.php       # Управление товарами
    ├── product-edit.php   # Добавление/редактирование товара
    ├── users.php          # Пользователи
    └── api.php            # Admin AJAX API
```

---

## 🚀 Установка на VPS (хостинг)

### 1. Требования к серверу
- PHP 8.0+
- MySQL 5.7+ или MariaDB 10.3+
- Apache 2.4+ с mod_rewrite
- Расширения PHP: PDO, PDO_MySQL, mbstring, fileinfo

### 2. Загрузка файлов на сервер

```bash
# Через FTP (FileZilla или аналог)
# Загрузите всю папку imsit_shop/ в /var/www/html/ или папку вашего домена

# Или через SCP:
scp -r ./imsit_shop/ user@your-server-ip:/var/www/html/
```

### 3. Создание базы данных

```bash
# Подключитесь к MySQL
mysql -u root -p

# Создайте БД и импортируйте схему
mysql -u root -p < /var/www/html/_shop/database.sql
```

**Или через phpMyAdmin:**
1. Откройте phpMyAdmin
2. Создайте базу данных `imsit_shop`
3. Выберите её и нажмите «Импорт»
4. Загрузите файл `database.sql`

### 4. Настройка конфигурации

Откройте файл `includes/config.php` и измените:

```php
define('DB_HOST', 'localhost');    // Хост БД (обычно localhost)
define('DB_NAME', 'imsit_shop');   // Имя базы данных
define('DB_USER', 'your_user');    // Пользователь MySQL
define('DB_PASS', 'your_pass');    // Пароль MySQL
define('SITE_URL', 'https://ваш-домен.ru');  // URL сайта
```

### 5. Права доступа к папкам

```bash
chmod 755 /var/www/html/imsit_shop/
chmod 777 /var/www/html/imsit_shop/public/images/
chmod 644 /var/www/html/imsit_shop/includes/config.php
```

### 6. Настройка Apache

Убедитесь что `mod_rewrite` включён:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

В конфиге виртуального хоста добавьте:
```apache
<Directory /var/www/html/imsit_shop>
    AllowOverride All
    Require all granted
</Directory>
```

---

## 🔐 Доступ к системе

### Администратор по умолчанию
- **Email:** admin@imsit.ru
- **Пароль:** admin123
- **Панель:** /admin/index.php

> ⚠️ **Важно:** После первого входа смените пароль в БД:
> ```sql
> UPDATE users SET password = '$2y$10$НОВЫЙ_ХЭШ' WHERE email = 'admin@imsit.ru';
> ```
> Или добавьте страницу смены пароля в аккаунте.

---

## ✨ Функциональность

### Покупатель
- 🏠 Главная страница с избранными товарами
- 🛍 Каталог с фильтрами по категориям и поиском
- 📄 Страница каждого товара
- 🛒 Корзина (работает без авторизации)
- 👤 Регистрация и вход
- 📦 Оформление заказа с выбором оплаты
- 🏠 Личный кабинет: история заказов, профиль
- 📋 Детальный просмотр заказа со статусом

### Администратор
- 📊 Дашборд со статистикой
- 📦 Управление заказами + изменение статуса
- 🖊 Добавление/редактирование/удаление товаров
- 📤 Загрузка изображений товаров
- 👥 Просмотр пользователей
- ✅ Фильтрация заказов по статусу

---

## 🎨 Технологии

| Слой       | Технология                        |
|------------|-----------------------------------|
| Бэкенд     | PHP 8 (без фреймворка)            |
| База данных| MySQL + PDO                       |
| Фронтенд   | HTML5 + CSS3 + Vanilla JavaScript |
| Шрифты     | Raleway + Playfair Display        |
| Сервер     | Apache + .htaccess                |

---

## 📸 Добавление изображений товаров

1. Загрузите изображение через **Админ → Товары → Редактировать**
2. Или вручную поместите в `public/images/`
3. В поле `image` в БД укажите имя файла

Рекомендуемый размер: **800×800px**, формат JPG или WebP.

---

## 🐛 Часто встречаемые проблемы

**Ошибка подключения к БД:**
→ Проверьте `includes/config.php` — логин, пароль, имя БД

**Не работает загрузка изображений:**
→ `chmod 777 public/images/`

**Страница не найдена (404):**
→ Проверьте что включён `mod_rewrite` и `AllowOverride All`

**Корзина не сохраняется:**
→ Проверьте что PHP сессии работают (`session_start`)
