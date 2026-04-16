# Database Backup Setup

Automatski backup baze je implementiran kroz Laravel scheduler i komandu:

- `db:backup`

## 1. ENV konfiguracija (produkcija)

Dodaj u `.env`:

```env
DB_BACKUP_ENABLED=true
DB_BACKUP_DAILY_AT=00:30
DB_BACKUP_TIMEZONE=Europe/Sarajevo
DB_BACKUP_RETENTION_DAYS=30
DB_BACKUP_DISK=local
DB_BACKUP_PATH=backups/database

# Opcionalno: dodatna sigurna kopija (npr. S3)
DB_BACKUP_SECURE_COPY_ENABLED=true
DB_BACKUP_SECURE_DISK=s3
DB_BACKUP_SECURE_PATH=backups/database
```

## 2. Ručni test backupa

```bash
php artisan db:backup
```

## 3. Scheduler (obavezno)

Na serveru mora postojati cron koji pokreće Laravel scheduler svake minute:

```bash
* * * * * cd /putanja/do/projekta/backend && php artisan schedule:run >> /dev/null 2>&1
```

## 4. Provjera rasporeda

```bash
php artisan schedule:list
```

Trebaš vidjeti `db:backup` na vremenu definisanom kroz `DB_BACKUP_DAILY_AT`.

