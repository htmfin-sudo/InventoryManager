
# StockFlow – Ghid pentru deschiderea rapoartelor CSV în Excel

## 1. Descriere

Aplicația StockFlow permite exportarea rapoartelor
în format CSV (Comma-Separated Values).

Sunt disponibile două tipuri de rapoarte:

- Raportul produselor
- Raportul mișcărilor de stoc

Fișierele CSV pot fi deschise în Microsoft Excel
pentru analizarea și prelucrarea datelor.

## 2. Formatul fișierelor

Rapoartele CSV generate de StockFlow utilizează:

- Codificare: UTF-8
- Separator între coloane: punct și virgulă (;)
- Extensie: .csv

În funcție de setările regionale ale calculatorului,
Excel poate afișa toate informațiile într-o singură
coloană atunci când fișierul este deschis direct.

Aceasta este o problemă de interpretare a formatului
CSV, nu o pierdere a datelor exportate.

## 3. Cum deschidem corect un CSV în Excel

### Pasul 1 – Descărcarea raportului

1. Autentificați-vă în aplicația StockFlow
   folosind un cont Administrator.
2. Accesați pagina Rapoarte.
3. Apăsați butonul de export CSV dorit.
4. Salvați fișierul pe calculator.

### Pasul 2 – Importarea în Excel

1. Deschideți Microsoft Excel.
2. Creați un registru de lucru gol.
3. Accesați meniul Date (Data).
4. Selectați Din text/CSV (From Text/CSV).
5. Alegeți fișierul CSV descărcat.
6. În fereastra de import, configurați:

   Originea fișierului (File Origin):
   65001: Unicode (UTF-8)

   Delimitator (Delimiter):
   Punct și virgulă (Semicolon ;)

7. Verificați previzualizarea datelor.
8. Apăsați Încărcare (Load).

## 4. Rezultatul importului

După importare, informațiile sunt afișate
în coloane separate.

Exemplu pentru raportul produselor:

| ID | Produs | Preț | Stoc |
|---|---|---|---|
| 1 | Laptop Lenovo | 3200 | 31 |
| 2 | Tastatură | 260 | 0 |
| 3 | Mouse Logitech | 327 | 0 |

Valorile sunt orientative.

Diacriticele românești trebuie să fie afișate
corect, inclusiv ă, â, î, ș și ț.

## 5. Salvarea raportului ca fișier Excel

După importarea fișierului CSV:

1. Selectați Fișier (File).
2. Apăsați Salvare ca (Save As).
3. Alegeți tipul Excel Workbook (*.xlsx).
4. Introduceți numele fișierului.
5. Apăsați Salvare (Save).

Fișierul rezultat este un document Excel
care poate fi deschis ulterior direct,
fără repetarea procesului de import.

## 6. Probleme frecvente

### Toate datele sunt într-o singură coloană

Cauză:
Excel nu a identificat separatorul utilizat.

Soluție:
Importați fișierul folosind delimitatorul
punct și virgulă (;).

### Diacriticele apar incorect

Exemplu:
TastaturÄƒ în loc de Tastatură.

Cauză:
Excel a interpretat greșit codificarea.

Soluție:
Selectați 65001: Unicode (UTF-8)
în fereastra de import.

### Prețurile sunt interpretate greșit

Excel poate interpreta diferit separatorul
zecimal, în funcție de configurarea regională.

Verificați valorile numerice după import.
Dacă este necesar, selectați tipul de date
corespunzător în Power Query.

## 7. Observații

CSV este un format standard pentru transferul
datelor între aplicații.

Fișierele CSV nu includ formatare vizuală,
culori, filtre sau lățimi prestabilite
ale coloanelor.

StockFlow generează rapoarte CSV.
Aplicația nu generează direct fișiere XLSX.

Pentru salvarea în format Excel, utilizați
procedura descrisă la secțiunea 5.

## 8. Proiect

Aplicație: StockFlow – InventoryManager

Autor: Fineas Hotima

Universitate: Universitatea Politehnica Timișoara

Repository:
https://github.com/htmfin-sudo/InventoryManager
