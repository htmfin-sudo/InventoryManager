# JURNAL AI – StockFlow (InventoryManager)

**Autor:** Fineas Hotima  
**Universitate:** Universitatea Politehnica Timișoara  
**Proiect:** InventoryManager – StockFlow  
**Instrument AI:** ChatGPT (OpenAI)  
**Data documentării:** 12.11.2026  
**GitHub:** https://github.com/htmfin-sudo/InventoryManager

---

## 1. Scopul utilizării inteligenței artificiale

În dezvoltarea proiectului StockFlow am utilizat ChatGPT pentru generarea codului, explicarea conceptelor de programare, proiectarea bazei de date, identificarea erorilor și realizarea documentației.

Aplicația a fost dezvoltată incremental, folosind Visual Studio Code, PHP, MySQL, HTML, CSS, JavaScript și Bootstrap.

Codul generat cu ajutorul AI a fost introdus în proiect, iar funcționalitățile au fost verificate prin rularea aplicației pe serverul local XAMPP.

Înregistrările următoare prezintă exemple de prompturi reale sau rezumate ale cererilor adresate AI, împreună cu rezultatele și metodele de verificare.

## 2. Alegerea temei

**Prompt – rezumat:**

„Care este cea mai ușoară și rapidă temă de proiect web care respectă cerințele profesorului și poate fi utilă pentru CV?”

**Rezultatul AI:**

A fost analizată lista temelor disponibile. Am ales InventoryManager, deoarece permite implementarea unei aplicații practice de gestionare a inventarului.

**Verificare:**

Am comparat proiectul propus cu cerințele universitare: minimum 4 pagini, bază de date relațională, operații CRUD, JavaScript, responsive design și GitHub.

## 3. Configurarea mediului de dezvoltare

**Prompt – rezumat:**

„Explică-mi pas cu pas cum să încep proiectul InventoryManager folosind VS Code și XAMPP.”

**Rezultatul AI:**

Am configurat structura proiectului în folderul `C:\xampp\htdocs\InventoryManager`, am pornit Apache și MySQL și am creat fișierul de conectare la baza de date.

**Verificare:**

Am accesat `http://localhost/InventoryManager/` și am verificat mesajul de conectare reușită la baza de date.

## 4. Crearea bazei de date

**Prompt – rezumat:**

„Generează baza de date MySQL pentru utilizatori, produse, categorii, furnizori și mișcări de stoc.”

**Rezultatul AI:**

Au fost create cinci tabele relaționale:

- users
- categories
- suppliers
- products
- stock_movements

Au fost definite chei primare și relații prin chei externe.

**Verificare:**

Am executat comenzile SQL în phpMyAdmin și am verificat existența tabelelor. Ulterior am introdus date de test.

## 5. Implementarea CRUD

**Prompt – rezumat:**

„Creează paginile PHP pentru adăugarea, vizualizarea, editarea și ștergerea produselor.”

**Rezultatul AI:**

Au fost implementate funcționalitățile CRUD pentru produse, categorii și furnizori, folosind PHP, formulare HTML și interogări PDO.

**Verificare:**

Am adăugat produse, am modificat prețuri și categorii, am șters produse și am verificat că modificările sunt persistente în baza de date.

## 6. Gestionarea intrărilor și ieșirilor de stoc

**Prompt – rezumat:**

„Implementează intrări și ieșiri de produse, calcularea automată a stocului și avertizări pentru stoc insuficient.”

**Rezultatul AI:**

A fost creată pagina Mișcări de stoc, cu calcularea cantităților disponibile pe baza operațiunilor înregistrate.

**Verificare:**

Am înregistrat două intrări de câte 3 bucăți și o ieșire de 4 bucăți.

Rezultatul a fost un stoc disponibil de 2 bucăți. Aplicația a afișat corect avertizarea pentru stocul minim de 5 bucăți.

## 7. Corectarea meniului lateral

**Prompt – rezumat al unei probleme reale:**

„Când apăs pe Categorii, dispare Furnizori, iar când apăs pe Produse, dispar alte opțiuni din meniu.”

**Rezultatul AI:**

AI a propus centralizarea meniului într-un singur fișier, `includes/sidebar.php`, reutilizat de toate paginile.

**Verificare:**

Am navigat între Dashboard, Produse, Categorii, Furnizori și Mișcări de stoc și am confirmat că meniul rămâne disponibil.

## 8. Căutarea și filtrarea produselor

**Prompt – rezumat:**

„Adaugă căutare după numele produsului și filtrare după categorie, furnizor și stoc.”

**Rezultatul AI:**

Au fost implementate filtre prin parametri GET și interogări SQL parametrizate.

**Verificare:**

Am căutat produse după nume, am filtrat după categorii și am verificat afișarea produselor cu stoc scăzut.

## 9. Configurarea Git și GitHub

**Prompt – rezumat:**

„Ajută-mă să configurez Git și să public proiectul pe GitHub, pas cu pas.”

**Rezultatul AI:**

Am inițializat repository-ul local, am configurat identitatea Git și am creat fișierul `.gitignore`.

Am publicat proiectul în repository-ul GitHub `htmfin-sudo/InventoryManager`.

**Probleme întâlnite:**

Primul commit a eșuat deoarece Git nu avea configurate numele și emailul autorului.

De asemenea, a fost necesară corectarea fișierului `.gitignore`.

**Verificare:**

Am folosit comenzile `git status`, `git check-ignore`, `git commit` și `git push`.

Am confirmat publicarea modificărilor pe ramura `main`.

## 10. Implementarea autentificării

**Prompt – rezumat:**

„Implementează un sistem Login și Logout cu roluri Administrator și Angajat.”

**Rezultatul AI:**

Au fost implementate sesiuni PHP, autentificare cu email și parolă, hashing al parolelor și controlul accesului în funcție de rol.

**Verificare:**

Am testat autentificarea administratorului și a unui angajat.

Am verificat că angajatul poate vizualiza produsele, dar nu poate accesa paginile administrative sau butoanele de editare și ștergere.

Am testat și redirecționarea utilizatorilor neautentificați către Login.

## 11. Administrarea angajaților

**Prompt real:**

„Trebuie să și pot șterge angajați ca admin.”

**Rezultatul AI:**

A fost implementată pagina Utilizatori, cu posibilitatea creării angajaților și gestionării stării conturilor.

Pentru utilizatorii cu istoric de operațiuni a fost introdusă dezactivarea, astfel încât istoricul să fie păstrat.

**Verificare:**

Am verificat afișarea conturilor, rolurilor, numărului de operațiuni și acțiunilor disponibile pentru administrator.

## 12. Schimbarea parolei

**Prompt – rezumat al unei cereri reale:**

„Vreau ca fiecare utilizator să își poată schimba parola doar dacă este autentificat.”

**Rezultatul AI:**

Am creat pagina `change_password.php`, care solicită parola actuală, parola nouă și confirmarea acesteia.

Actualizarea afectează exclusiv contul autentificat.

**Verificare:**

Am testat schimbarea parolei și autentificarea folosind parola nouă.

## 13. Dashboard Analytics

**Prompt – rezumat:**

„Adaugă grafice interactive și statistici pentru stocurile produselor și valoarea inventarului.”

**Rezultatul AI:**

Am integrat Chart.js pentru afișarea graficelor.

Au fost implementate grafice pentru stocurile produselor, distribuția pe categorii și comparația intrărilor cu ieșirile.

**Verificare:**

Am verificat graficele în browser.

La unul dintre teste, aplicația afișa 4 produse, 88 de unități disponibile și o valoare totală a inventarului de 352.508 lei.

## 14. Rapoarte și exporturi

**Prompt – rezumat:**

„Creează o pagină pentru rapoarte, cu filtrare, export CSV și salvare PDF.”

**Rezultatul AI:**

Am implementat pagina Rapoarte, cu filtre pentru produse și mișcări de stoc, export CSV și o versiune pregătită pentru imprimare sau salvare PDF din browser.

**Verificare:**

Am verificat afișarea rapoartelor și a statisticilor în interfață. Verificarea fișierelor exportate face parte din testarea finală.

## 15. Utilizarea AI pentru depanare

Pe parcursul proiectului, am transmis către ChatGPT capturi din VS Code, phpMyAdmin, terminal și browser.

Acestea au fost folosite pentru identificarea problemelor și verificarea rezultatelor.

Un exemplu de solicitare reală a fost:

„Dă-mi mereu codul înlocuitor complet.”

Am adoptat o metodă de dezvoltare în care codul complet al unui fișier era înlocuit, salvat și testat înainte de continuarea proiectului.

Această abordare a redus dificultatea integrării modificărilor, dar a necesitat verificarea păstrării funcționalităților existente.

## 16. Limitări și verificare independentă

Codul sugerat de AI nu a fost considerat automat corect.

Verificarea a inclus executarea aplicației pe calculatorul local, testarea formularelor, verificarea operațiilor de stoc, autentificarea cu roluri diferite și inspectarea rezultatelor.

AI nu a avut acces direct la execuția aplicației locale. Rezultatele au fost verificate de autor și comunicate prin mesaje și capturi de ecran.

Înaintea publicării aplicației pe un server de producție sunt necesare teste suplimentare de securitate.

## 17. Concluzie

ChatGPT a fost utilizat ca instrument de asistență pentru proiectarea, implementarea, depanarea și documentarea aplicației StockFlow.

Prin dezvoltarea proiectului am lucrat cu HTML, CSS, JavaScript, PHP, MySQL, baze de date relaționale, operații CRUD, autentificare, autorizare, rapoarte și versionare Git.

Utilizarea AI a accelerat dezvoltarea, însă a fost necesară verificarea practică a fiecărei funcționalități.

**Concluzia principală:** inteligența artificială poate sprijini dezvoltarea software, dar rezultatele generate trebuie analizate, testate și înțelese de dezvoltator.

---

**Autor:** Fineas Hotima  
**Proiect:** StockFlow – InventoryManager  
**Repository:** https://github.com/htmfin-sudo/InventoryManager