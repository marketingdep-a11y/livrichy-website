# ✅ Полный список импортов Statamic Eloquent Driver

## 📋 Все возможные команды импорта:

### ✅ Импортируется в нашем entrypoint скрипте:

1. **Sites** ✅
   - `statamic:eloquent:import-sites`
   - Файлы: `config/sites.php` → база данных

2. **Asset Containers** ✅
   - `statamic:eloquent:import-assets --force --only-asset-containers`
   - Файлы: `content/assets/*.yaml` → база данных

3. **Assets** ✅
   - `statamic:eloquent:import-assets --force --only-assets`
   - Файлы: метаданные assets → база данных

4. **Blueprints** ✅
   - `statamic:eloquent:import-blueprints --force --only-blueprints`
   - Файлы: `resources/blueprints/**/*.yaml` → база данных

5. **Fieldsets** ✅
   - `statamic:eloquent:import-blueprints --force --only-fieldsets`
   - Файлы: `resources/fieldsets/*.yaml` → база данных

6. **Collections** ✅
   - `statamic:eloquent:import-collections --force`
   - Файлы: `content/collections/*.yaml` → база данных

7. **Collection Trees** ✅ (добавлено явно)
   - `statamic:eloquent:import-collections --force --only-collection-trees`
   - Файлы: `content/trees/collections/*.yaml` → база данных

8. **Taxonomies** ✅
   - `statamic:eloquent:import-taxonomies --force --only-taxonomies`
   - Файлы: `content/taxonomies/*.yaml` → база данных

9. **Taxonomy Terms** ✅
   - `statamic:eloquent:import-taxonomies --force --only-terms`
   - Файлы: `content/taxonomies/**/*.yaml` → база данных

10. **Entries** ✅
    - `statamic:eloquent:import-entries`
    - Файлы: `content/collections/**/*.md` → база данных

11. **Navigations** ✅
    - `statamic:eloquent:import-navs --force --only-navs`
    - Файлы: `content/navigation/*.yaml` → база данных

12. **Navigation Trees** ✅
    - `statamic:eloquent:import-navs --force --only-nav-trees`
    - Файлы: `content/trees/navigation/*.yaml` → база данных

13. **Global Sets** ✅
    - `statamic:eloquent:import-globals --force --only-global-sets`
    - Файлы: `content/globals/*.yaml` → база данных

14. **Global Variables** ✅
    - `statamic:eloquent:import-globals --force --only-global-variables`
    - Файлы: переменные из `content/globals/*.yaml` → база данных

15. **Forms** ✅
    - `statamic:eloquent:import-forms --force --only-forms`
    - Файлы: `resources/forms/*.yaml` → база данных

16. **Form Submissions** ✅ (опционально)
    - `statamic:eloquent:import-forms --force --only-form-submissions`
    - Файлы: submissions из `storage/forms/` → база данных

17. **Revisions** ✅ (опционально, только если включены)
    - `statamic:eloquent:import-revisions`
    - Файлы: `storage/statamic/revisions/` → база данных

### ❌ НЕ импортируется (обычно не нужно при деплое):

- **Users** - создаются вручную в админ-панели
- **Roles** - создаются вручную в админ-панели
- **Groups** - создаются вручную в админ-панели

## 📝 Важные замечания:

1. **Порядок импорта критичен:**
   - Sites → Assets → Blueprints → Collections → Taxonomies → Entries → Navigations → Globals → Forms

2. **Опции команд:**
   - Некоторые команды поддерживают `--force`, некоторые нет
   - `import-entries` и `import-sites` НЕ поддерживают `--force`
   - Остальные команды поддерживают `--force`

3. **Collection Trees:**
   - Импортируются автоматически вместе с collections
   - Но лучше импортировать явно для уверенности

4. **Revisions:**
   - Импортируются только если включены в конфиге
   - По умолчанию отключены


