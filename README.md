
# StockFlow – InventoryManager

Aplicatie web pentru gestionarea inventarului,
dezvoltata ca proiect universitar.

Autor: Fineas Hotima
Universitate: Universitatea Politehnica Timisoara

## Tehnologii utilizate

- HTML5, CSS3 si Bootstrap 5
- JavaScript si Chart.js
- PHP
- MySQL / MariaDB
- PDO
- Git si GitHub

## Functionalitati

### Dashboard
- Statistici despre produse si stocuri
- Valoarea totala a inventarului
- Grafice interactive
- Avertizari pentru stoc scazut

### Gestionarea produselor
- Adaugare produse
- Afisare si cautare produse
- Editare si stergere produse
- Filtrare dupa categorie, furnizor si stoc

### Categorii si furnizori
- Operatii CRUD
- Asocierea produselor cu categorii si furnizori

### Miscari de stoc
- Inregistrarea intrarilor (IN)
- Inregistrarea iesirilor (OUT)
- Calcularea stocului disponibil
- Validarea cantitatilor
- Istoricul operatiunilor

### Autentificare
- Login si Logout
- Roluri Administrator si Angajat
- Gestionarea conturilor de angajati
- Schimbarea parolei
- Restrictionarea accesului dupa rol

### Rapoarte
- Filtrarea produselor si miscarilor
- Export CSV
- Imprimare si salvare PDF din browser

### API REST
- Endpoint PHP pentru consultarea produselor
- Raspunsuri JSON
- Cautare si filtrare
- Autentificare prin sesiunea aplicatiei
- Metoda HTTP GET

Documentatie API:
[docs/API.md](docs/API.md)

## Baza de date

Baza de date se numeste `inventory_manager`.

Contine 5 tabele relationale:

1. users
2. categories
3. suppliers
4. products
5. stock_movements

Fisierul de creare a bazei de date:
`sql/schema.sql`

## Instalare locala

1. Instalati XAMPP.
2. Porniti Apache si MySQL.
3. Copiati proiectul in:
   `C:\xampp\htdocs\InventoryManager`
4. Deschideti phpMyAdmin.
5. Importati fisierul `sql/schema.sql`.
6. Copiati `config/db.example.php`
   in `config/db.php`.
7. Configurati datele locale de conectare.
8. Creati un cont administrator initial
   in tabelul `users`, cu parola stocata
   folosind `password_hash()` din PHP.
9. Accesati aplicatia la:
   http://localhost/InventoryManager/login.php

Nota: Fisierul `config/db.php` contine
configurarea locala si nu trebuie publicat
pe GitHub.

## Rolurile utilizatorilor

Administrator:
- Gestionare produse
- Gestionare categorii si furnizori
- Gestionare angajati
- Miscari de stoc
- Rapoarte si exporturi
- Acces la Dashboard si API

Angajat:
- Vizualizare produse
- Inregistrare miscari de stoc
- Acces la Dashboard si API
- Schimbarea propriei parole

## API

Endpoint:

GET /InventoryManager/api/products.php

Exemple:

Lista produselor:
`api/products.php`

Cautare:
`api/products.php?q=Laptop`

Stoc scazut:
`api/products.php?stock=low`

Produs dupa ID:
`api/products.php?id=1`

API-ul necesita autentificare.

## Documentatie

- [Jurnalul utilizarii AI](docs/JURNAL_AI.md)
- [Documentatia API](docs/API.md)
- [Schema bazei de date](sql/schema.sql)

## Utilizarea inteligentei artificiale

ChatGPT a fost utilizat ca instrument
de asistenta pentru dezvoltarea,
explicarea, depanarea si documentarea
aplicatiei.

Prompturile, rezultatele si metodele
de verificare sunt prezentate in
`docs/JURNAL_AI.md`.

## Limitari

Aplicatia este destinata demonstrarii
conceptelor de dezvoltare web in
cadrul unui proiect universitar.

Nu a fost realizat un audit complet
de securitate pentru productie.

## Repository

https://github.com/htmfin-sudo/InventoryManager
