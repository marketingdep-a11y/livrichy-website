# Настройка Google Sheets для импорта агентов

## Шаг 1: Создание Google Cloud Project

1. Перейдите на [Google Cloud Console](https://console.cloud.google.com/)
2. Создайте новый проект или выберите существующий
3. Включите Google Sheets API:
   - В меню выберите "APIs & Services" → "Enable APIs and Services"
   - Найдите "Google Sheets API" и нажмите "Enable"

## Шаг 2: Создание Service Account

1. Перейдите в "APIs & Services" → "Credentials"
2. Нажмите "Create Credentials" → "Service Account"
3. Заполните имя (например, "Livrichy Agents Sync")
4. Нажмите "Create and Continue"
5. В разделе "Grant this service account access to project" выберите роль "Editor"
6. Нажмите "Done"

## Шаг 3: Создание JSON ключа

1. В списке Service Accounts найдите созданный аккаунт
2. Нажмите на него, перейдите во вкладку "Keys"
3. Нажмите "Add Key" → "Create new key"
4. Выберите формат JSON
5. Файл автоматически скачается (например, `project-name-123456-abc123def456.json`)

## Шаг 4: Установка credentials файла

1. Переименуйте скачанный JSON файл в `google-credentials.json`
2. Поместите его в папку `storage/app/` вашего проекта
3. Убедитесь, что путь к файлу: `/Users/paveltraskou/Documents/Livrichy/mysite/storage/app/google-credentials.json`

## Шаг 5: Предоставление доступа к таблице

1. Откройте скачанный JSON файл и найдите поле `client_email`
2. Скопируйте email (например, `livrichy-agents-sync@project-id.iam.gserviceaccount.com`)
3. Откройте ваш Google Sheets документ
4. Нажмите кнопку "Share" (Поделиться)
5. Вставьте скопированный email
6. Установите права доступа "Viewer" (Просмотр)
7. Нажмите "Send" (Отправить)

## Шаг 6: Получение Spreadsheet ID

1. Откройте ваш Google Sheets документ в браузере
2. Скопируйте URL из адресной строки
3. URL выглядит так: `https://docs.google.com/spreadsheets/d/YOUR_SPREADSHEET_ID/edit`
4. `YOUR_SPREADSHEET_ID` - это и есть ID вашей таблицы (длинная строка между `/d/` и `/edit`)

## Шаг 7: Настройка переменных окружения

Добавьте следующие переменные в ваш `.env` файл:

```env
GOOGLE_SHEETS_AGENTS_SPREADSHEET_ID=10MZqpTOdcx8WuUlT5iR1NXS_TqydDYEY7H36Yr-97fU
GOOGLE_SHEETS_AGENTS_RANGE=Agents!A:H
GOOGLE_SHEETS_AGENTS_CREDENTIALS_PATH=/Users/paveltraskou/Documents/Livrichy/mysite/storage/app/google-credentials.json
```

Замените `YOUR_SPREADSHEET_ID` на реальный ID вашей таблицы.

## Шаг 8: Проверка работы

Запустите команду для тестового импорта:

```bash
cd /Users/paveltraskou/Documents/Livrichy/mysite
php artisan import:google-sheets-agents
```

Если все настроено правильно, вы увидите отчет о синхронизации агентов.

**Примечание:** Команда автоматически увеличивает memory_limit до 512M для работы с Google API.

## Формат таблицы

Ваша Google Sheets таблица должна иметь следующую структуру:

| A | B | C | D | E | F | G | H |
|---|---|---|---|---|---|---|---|
| Agent' ID | LastUpdated | Department | Name Of Agent | Position | Photo | Bio | Status |
| 11 | 2025-11-01 | Mohamed team | John Doe | Real Estate Agent | https://... | Bio text | Published |
| ... | ... | ... | ... | ... | ... | ... | ... |

**Важно:**
- Первая строка (заголовки) пропускается при импорте (поэтому используется диапазон A2:H)
- Только агенты со статусом "Published" будут импортированы
- Агенты, которых нет в списке "Published", будут удалены из Statamic

## Автоматическая синхронизация

Импорт настроен на автоматический запуск каждую среду в 18:00 UAE (14:00 UTC).

Убедитесь, что у вас запущен Laravel Scheduler:

```bash
# В cron добавьте:
* * * * * cd /Users/paveltraskou/Documents/Livrichy/mysite && php artisan schedule:run >> /dev/null 2>&1
```

## Решение проблем

### Ошибка "credentials file not found"
Убедитесь, что путь к файлу credentials правильный и файл существует.

### Ошибка "insufficient permissions"
Убедитесь, что Service Account имеет доступ к вашей Google Sheets таблице (см. Шаг 5).

### Ошибка "spreadsheet not found"
Проверьте правильность Spreadsheet ID в переменной окружения.

### Агенты не импортируются
Убедитесь, что в колонке H (Status) указано именно "Published" (с заглавной буквы).

