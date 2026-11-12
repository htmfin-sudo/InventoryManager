
# StockFlow REST API

## Descriere

StockFlow include un API REST pentru consultarea
produselor și a stocurilor disponibile.

API-ul este implementat folosind PHP, PDO și MySQL.

Endpoint:

GET /InventoryManager/api/products.php

URL local:

http://localhost/InventoryManager/api/products.php

## Autentificare

API-ul folosește sesiunea PHP a aplicației StockFlow.

Pentru acces, utilizatorul trebuie să fie autentificat.

Sunt acceptate rolurile:
- Administrator
- Angajat

API-ul nu este public și nu folosește API keys.

## Funcționalități

API-ul permite:

1. Listarea produselor.
2. Căutarea produselor după nume.
3. Filtrarea după categorie.
4. Filtrarea după stoc scăzut sau normal.
5. Obținerea unui produs după ID.
6. Limitarea numărului de rezultate.

API-ul permite doar metoda GET.

Operațiile de adăugare, editare și ștergere
se realizează în interfața administrativă.

## Exemple de utilizare

### 1. Toate produsele

GET /InventoryManager/api/products.php

### 2. Căutare după nume

GET /InventoryManager/api/products.php?q=Laptop

### 3. Filtrare după categorie

GET /InventoryManager/api/products.php?category_id=1

### 4. Produse cu stoc scăzut

GET /InventoryManager/api/products.php?stock=low

### 5. Produse cu stoc normal

GET /InventoryManager/api/products.php?stock=ok

### 6. Produs după ID

GET /InventoryManager/api/products.php?id=1

### 7. Limitarea rezultatelor

GET /InventoryManager/api/products.php?limit=10

## Formatul răspunsului

API-ul returnează date în format JSON.

Exemplu ilustrativ:

{
  "success": true,
  "count": 1,
  "data": [
    {
      "id": 1,
      "name": "Laptop Lenovo IdeaPad",
      "category_id": 1,
      "category": "Laptopuri",
      "supplier_id": 1,
      "supplier": "Furnizor Demo",
      "price": "2500.00",
      "currency": "RON",
      "current_stock": 2,
      "min_stock": 5,
      "stock_status": "low"
    }
  ]
}

Valorile sunt exemple, nu date fixe.

## Coduri HTTP

- 200 OK: solicitare procesată cu succes.
- 400 Bad Request: parametri invalizi.
- 404 Not Found: produs inexistent.
- 405 Method Not Allowed: metoda HTTP neacceptată.
- 500 Internal Server Error: eroare internă.

În cazul utilizatorilor neautentificați,
mecanismul existent de autentificare poate
redirecționa cererea către pagina Login.

## Securitate

API-ul utilizează:

- Sesiuni PHP pentru autentificare.
- Controlul accesului pe roluri.
- Interogări SQL parametrizate.
- Validarea parametrilor primiți.
- Limitarea rezultatelor la maximum 100.
- Restricționarea metodei HTTP la GET.
- Răspunsuri JSON fără mesaje SQL detaliate.

## Tehnologii

- PHP
- MySQL
- PDO
- JSON
- HTTP GET

## Testare

API-ul poate fi testat în browser, după
autentificarea în aplicația StockFlow.

Se recomandă testarea:
- Listei complete de produse.
- Căutării după nume.
- Filtrului de stoc scăzut.
- Solicitării unui ID inexistent.
- Accesului fără autentificare.
- Respingerii parametrilor invalizi.

## Limitări

API-ul este destinat demonstrării
funcționalităților proiectului universitar.

Folosește autentificarea prin sesiune,
nu OAuth sau autentificare cu token.

Pentru utilizare publică în producție
ar fi necesare măsuri suplimentare
de securitate și testare.
