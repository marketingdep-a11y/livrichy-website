# 🔧 Быстрое решение проблемы 403 при клонировании

## Проблема:
```
remote: Write access to repository not granted.
fatal: unable to access 'https://github.com/...': The requested URL returned error: 403
```

## Причина:
GitHub больше не принимает пароли для HTTPS клонирования приватных репозиториев. Нужен либо:
- Personal Access Token
- SSH ключ

## Решение 1: SSH ключ (самый надежный)

### На сервере Хостингера выполните:

```bash
# 1. Создайте SSH ключ
ssh-keygen -t ed25519 -C "hostinger-deploy"
# Нажмите Enter для всех вопросов (или задайте пароль для ключа)

# 2. Покажите публичный ключ
cat ~/.ssh/id_ed25519.pub
```

### Скопируйте вывод и добавьте в GitHub:

1. Перейдите в GitHub → Settings (настройки профиля)
2. SSH and GPG keys → New SSH key
3. Название: `Hostinger Server`
4. Вставьте скопированный ключ
5. Add SSH key

### Теперь клонируйте через SSH:

```bash
cd /home/u619535552/public_html

# Удалите предыдущую попытку клонирования (если есть)
rm -rf public_html 2>/dev/null || true

# Клонируйте через SSH
git clone git@github.com:marketingdep-a11y/livrichy-website.git public_html

cd public_html/mysite
```

---

## Решение 2: Personal Access Token (быстрее)

### 1. Создайте токен в GitHub:

1. Перейдите: GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token (classic)
3. Название: `Hostinger Deploy`
4. Выберите срок действия (рекомендую: 90 дней или No expiration)
5. Выберите scope: **`repo`** (весь доступ к репозиториям)
6. Generate token
7. **ВАЖНО:** Скопируйте токен сразу (он больше не покажется!)

### 2. На сервере клонируйте с токеном:

```bash
cd /home/u619535552/public_html

# Удалите предыдущую попытку (если есть)
rm -rf public_html 2>/dev/null || true

# Клонируйте (при запросе используйте токен)
git clone https://github.com/marketingdep-a11y/livrichy-website.git public_html

# При запросе:
# Username: marketingdep-a11y
# Password: вставьте_ваш_токен (НЕ пароль от GitHub!)
```

---

## Если токен не помог:

Попробуйте указать токен в URL:

```bash
git clone https://marketingdep-a11y:YOUR_TOKEN_HERE@github.com/marketingdep-a11y/livrichy-website.git public_html
```

(Замените `YOUR_TOKEN_HERE` на ваш токен)

---

## После успешного клонирования:

```bash
cd /home/u619535552/public_html/mysite

# Создайте .env файл
cp .env.example .env

# Сгенерируйте APP_KEY
php artisan key:generate

# Установите зависимости
composer install --no-dev --optimize-autoloader --no-interaction
```

---

**Рекомендация:** Используйте SSH ключ (Решение 1) - это надежнее и удобнее для будущих обновлений!


