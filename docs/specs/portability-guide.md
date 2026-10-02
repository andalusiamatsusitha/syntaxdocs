# Panduan Portabilitas Arsitektur: Node.js & Golang Integration

Panduan ini adalah referensi resmi bagi pengembang yang ingin membuat produk baru di ekosistem SyntaxDocs menggunakan **Node.js** atau **Golang**, sekaligus tetap memanfaatkan **Shared UI CDN** dan pola arsitektur yang seragam dengan produk PHP.

---

## 1. Prinsip Utama: Zero-Touch Frontend

Aset UI (CSS, Icon, Font, dan Vanilla JS event engine) **100% netral bahasa**. Baik aplikasi PHP, Node.js, maupun Golang, seluruhnya memuat aset statis yang sama dari host Central CDN:

```html
<!-- Disematkan pada tag <head> layout utama -->
<link rel="stylesheet" href="http://cdn.company.local/css/core.min.css">

<!-- Disematkan sebelum penutup </body> -->
<script src="http://cdn.company.local/js/core.min.js"></script>
```

Seluruh interaktivitas modal dialog (`data-toggle="modal"`), dropdown menu (`data-toggle="dropdown"`), dismiss alert (`data-dismiss="alert"`), dan responsive sidebar langsung berfungsi otomatis di browser tanpa perlu menginstal pustaka JavaScript backend apa pun.

---

## 2. Pemetaan Pola Kode (Architecture Pattern Matrix)

| Konsep | PHP (`syntax/core-php`) | Node.js (Express / Fastify) | Golang (Chi / Fiber) |
| :--- | :--- | :--- | :--- |
| **HTTP Routing** | `Router::get('/items', [ItemCtrl::class, 'index'])` | `router.get('/items', itemController.index)` | `r.Get("/items", itemHandler.Index)` |
| **Route Grouping** | `Router::group(['prefix' => '/admin', 'middleware' => [...]], ...)` | `app.use('/admin', authMiddleware, adminRouter)` | `r.Route("/admin", func(r chi.Router) { r.Use(AuthMiddleware); ... })` |
| **Middleware** | `handle(Request $req, callable $next): Response` | `(req, res, next) => { ... next(); }` | `func(next http.Handler) http.Handler` |
| **Database Pool** | `ConnectionManager::connection('default')` | `knex` / `mysql2.createPool(...)` | `sqlx.Connect("mysql", dsn)` / GORM |
| **Query Builder** | `DB::table('users')->where('id', 1)->first()` | `db('users').where({ id: 1 }).first()` | `db.Table("users").Where("id = ?", 1).First(&user)` |
| **Auth Hash** | `PasswordHasher::hash($password)` | `bcrypt.hash(password, 12)` / `argon2` | `bcrypt.GenerateFromPassword(...)` |
| **JSON Response** | `Response::json($envelope, $status)` | `res.status(status).json(envelope)` | `json.NewEncoder(w).Encode(envelope)` |
| **Component** | `Component::render('modal', $props)` | `renderComponent('modal', props)` | `RenderComponent("modal", props)` |

---

## 3. Contoh Implementasi di Node.js (Express / Fastify)

### 3.1. Struktur Modul di Node.js
```text
apps/ecommerce-node/
├── src/
│   ├── middlewares/
│   │   ├── auth.middleware.js
│   │   └── security.middleware.js
│   ├── components/
│   │   └── component-renderer.js      # Mengikuti docs/specs/components-spec.json
│   ├── routes/
│   │   └── web.js
│   └── app.js
├── package.json
└── .env
```

### 3.2. Component Helper di Node.js (`src/components/component-renderer.js`)
```javascript
// Mengikuti kontrak docs/specs/components-spec.json
export function renderModal({ id, title, body, cancel_label = 'Batal', confirm_label, confirm_action }) {
  return `
    <div class="c-modal" id="${escapeHtml(id)}" aria-hidden="true" role="dialog">
      <div class="c-modal__backdrop" data-dismiss="modal"></div>
      <div class="c-modal__dialog">
        <div class="c-modal__header">
          <h4 class="c-modal__title">${escapeHtml(title)}</h4>
          <button type="button" class="c-modal__close" data-dismiss="modal">&times;</button>
        </div>
        <div class="c-modal__body">${body}</div>
        <div class="c-modal__footer">
          <button type="button" class="c-btn c-btn--secondary" data-dismiss="modal">${escapeHtml(cancel_label)}</button>
          ${confirm_label && confirm_action ? `
            <form method="POST" action="${escapeHtml(confirm_action)}" style="display:inline;">
              <button type="submit" class="c-btn c-btn--primary">${escapeHtml(confirm_label)}</button>
            </form>` : ''}
        </div>
      </div>
    </div>
  `;
}
```

---

## 4. Contoh Implementasi di Golang (Chi / Net/HTTP)

### 4.1. Struct Envelope Respon API Standar (`response.go`)
```go
package core

type ApiEnvelope struct {
    Success bool        `json:"success"`
    Code    int         `json:"code"`
    Message string      `json:"message"`
    Data    interface{} `json:"data"`
    Errors  interface{} `json:"errors"`
    Meta    *MetaInfo   `json:"meta,omitempty"`
}

type MetaInfo struct {
    Timestamp int64 `json:"timestamp"`
    Page      int   `json:"page,omitempty"`
    PerPage   int   `json:"per_page,omitempty"`
    Total     int   `json:"total,omitempty"`
}
```

### 4.2. Middleware Standar di Golang (`middleware.go`)
```go
package middleware

import "net/http"

func SecurityHeaders(next http.Handler) http.Handler {
    return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
        w.Header().Set("X-Content-Type-Options", "nosniff")
        w.Header().Set("X-Frame-Options", "SAMEORIGIN")
        w.Header().Set("X-XSS-Protection", "1; mode=block")
        w.Header().Set("Referrer-Policy", "strict-origin-when-cross-origin")
        next.ServeHTTP(w, r)
    })
}
```

---

## 5. Kesimpulan
Dengan mematuhi spesifikasi di folder `docs/specs/`, tim bebas menggunakan teknologi bahasa pemrograman terbaik untuk tiap use-case (PHP untuk rapid development dan CMS, Golang untuk layanan backend berkecepatan tinggi, dan Node.js untuk event-driven apps) tanpa mengorbankan konsistensi brand, antarmuka pengguna, ataupun struktur database.
