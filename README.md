# Advanced Booking System (ABS) Plugin

Advanced Booking System (ABS) este un plugin WordPress dedicat gestionării și organizării rezervărilor. Acesta oferă funcționalități avansate atât pentru utilizatori, cât și pentru administratori, fiind ușor de utilizat și configurat.

---

## 📋 Funcționalități principale

### Pentru utilizatori:
- **Formular de rezervare prietenos**: Permite utilizatorilor să facă rezervări rapid folosind un shortcode.
- **Afișare disponibilitate în timp real**: Integrare pentru afișarea orelor disponibile.
- **Personalizare rezervări**: Selectarea serviciilor și personalului direct din formular.

### Pentru administratori:
- **Gestionare servicii**:
  - Adăugare, editare și ștergere servicii.
  - Setarea prețurilor, duratei și statusului serviciilor.
- **Managementul personalului**:
  - Adăugare membri ai echipei și asocierea acestora cu utilizatori WordPress.
  - Gestionare informații precum email, telefon și biografie.
- **Dashboard detaliat**:
  - Statistici pentru rezervările din ziua curentă, săptămână și lună.
  - Vizualizarea veniturilor totale.
- **Rezervări**:
  - Listarea tuturor rezervărilor cu filtre pentru status și perioade.
  - Gestionarea statusului rezervărilor (Pending, Confirmed, Completed, Cancelled).
- **Setări avansate**:
  - Configurarea duratei sloturilor, notificărilor email și orelor de lucru.

---

## 🛠️ Instalare

1. **Descărcare și încărcare:**
   - Descărcați pluginul din acest repository.
   - Încărcați fișierul `.zip` în secțiunea `Plugins > Add New > Upload` din WordPress.

2. **Activare:**
   - Activați pluginul din secțiunea `Plugins` din WordPress.

3. **Configurare:**
   - Accesați secțiunea `Bookings` din panoul de administrare pentru a configura pluginul.

---

## 🔧 Utilizare

### Shortcode pentru rezervări
Adăugați următorul shortcode în orice pagină sau postare pentru a afișa formularul de rezervare:
```
[booking_form]
```

#### Parametri suportati:
- `service_id` - Pre-selectarea unui serviciu specific.
- `staff_id` - Pre-selectarea unui membru al personalului.
- `theme` - Selectarea unei teme pentru formular (ex: `default`, `modern`, `minimal`).

Exemplu:
```
[booking_form service_id="1" theme="modern"]
```

---

## 📂 Structura fișierelor

- **`booking-plugin.php`**: Punctul de intrare al pluginului.
- **`admin/`**: Funcționalități administrative (gestionare servicii, personal, rezervări, setări).
- **`public/`**: Funcționalități frontend (formulare rezervări).
- **`assets/css/` și `assets/js/`**: Stiluri și scripturi pentru interfață.
- **`includes/`**: Clase și funcții de backend.

---

## 👩‍💻 Contribuții

Contribuțiile sunt binevenite! Deschideți un issue sau un pull request pentru a colabora la dezvoltarea acestui plugin.

---

## 📞 Suport

Pentru întrebări și asistență, vă rugăm să deschideți un issue în acest repository.

---

## 📜 Licență

Acest plugin este distribuit sub licența [MIT](LICENSE).
