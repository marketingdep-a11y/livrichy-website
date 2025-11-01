# Настройка Google Sheets на продакшене

## Для локальной разработки

Используйте файл `storage/app/google-credentials.json`:
```bash
# Просто поместите ваш JSON файл сюда:
storage/app/google-credentials.json
```

## Для продакшена (Coolify)

### Шаг 1: Создайте Base64 строку из вашего credentials файла

```bash
# На Mac:
cat storage/app/google-credentials.json | base64

# На Linux:
cat storage/app/google-credentials.json | base64 -w 0
```

Скопируйте всю полученную строку (это будет длинная строка символов).

### Шаг 2: Добавьте переменные в Coolify

В панели Coolify добавьте следующие переменные окружения:

```env
# ID вашей Google Sheets таблицы (из URL)
GOOGLE_SHEETS_AGENTS_SPREADSHEET_ID=ваш_spreadsheet_id_здесь

# Диапазон ячеек для чтения
GOOGLE_SHEETS_AGENTS_RANGE=Table1!A2:H

# Base64 закодированные credentials
GOOGLE_CREDENTIALS_BASE64=вставьте_сюда_длинную_base64_строку_из_шага_1
```

### Шаг 3: Редеплой

После добавления переменных окружения сделайте редеплой приложения в Coolify.

## Проверка

После деплоя подключитесь к серверу и запустите:

```bash
php artisan import:google-sheets-agents
```

Если всё настроено правильно, вы увидите отчет о синхронизации агентов.

## Как работает

- **Локально**: система ищет файл `storage/app/google-credentials.json`
- **На продакшене**: система использует `GOOGLE_CREDENTIALS_BASE64` из переменных окружения
- Если есть `GOOGLE_CREDENTIALS_BASE64`, файл не нужен

## Безопасность

✅ Credentials НЕ хранятся в Git  
✅ Credentials передаются через переменные окружения  
✅ Файл `google-credentials.json` в `.gitignore`  

## Автоматическая синхронизация

Импорт запускается автоматически каждую среду в 18:00 UAE (14:00 UTC).

Убедитесь, что Laravel Scheduler настроен в cron:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

В Coolify это обычно настроено автоматически через supervisor или systemd.

