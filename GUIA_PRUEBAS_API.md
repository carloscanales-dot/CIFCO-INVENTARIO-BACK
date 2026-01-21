# Guía de Pruebas - API de Sesiones de Usuarios

## Requisitos Previos

1. Backend Laravel corriendo (puerto 8000 o el que uses)
2. Base de datos con la tabla `user_sessions` creada
3. Usuario con rol Admin o Super Admin en la base de datos
4. Token JWT válido para autenticación

## Obtener Token JWT (Login)

**POST** `http://localhost:8000/api/auth/login`

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "email": "admin@example.com",
  "password": "tu_password"
}
```

**Respuesta Esperada:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "full_name": "Admin User",
    "email": "admin@example.com",
    ...
  }
}
```

**Importante:** Guarda el `access_token` para usarlo en las siguientes peticiones.

---

## 1. Listar Todas las Sesiones

**GET** `http://localhost:8000/api/user-sessions`

**Headers:**
```
Authorization: Bearer {tu_access_token}
Content-Type: application/json
```

**Respuesta Esperada (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 5,
        "name": "Juan Pérez",
        "email": "juan@example.com",
        "avatar": "http://localhost:8000/storage/avatars/user.jpg"
      },
      "ip_address": "192.168.1.100",
      "device_type": "desktop",
      "browser": "Chrome",
      "platform": "Windows",
      "location": null,
      "login_at": "2026-01-18 10:30:00",
      "logout_at": null,
      "is_active": true,
      "duration": "2 hours ago",
      "created_at": "2026-01-18 10:30:00"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/user-sessions?page=1",
    "last": "http://localhost:8000/api/user-sessions?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/user-sessions?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 42
  }
}
```

---

## 2. Listar Solo Sesiones Activas

**GET** `http://localhost:8000/api/user-sessions?status=active`

**Headers:**
```
Authorization: Bearer {tu_access_token}
Content-Type: application/json
```

---

## 3. Buscar Sesiones por Usuario

**GET** `http://localhost:8000/api/user-sessions?search=juan`

**Headers:**
```
Authorization: Bearer {tu_access_token}
Content-Type: application/json
```

**Descripción:** Busca sesiones donde el nombre o email del usuario contenga "juan".

---

## 4. Paginación Personalizada

**GET** `http://localhost:8000/api/user-sessions?page=2&per_page=20`

**Headers:**
```
Authorization: Bearer {tu_access_token}
Content-Type: application/json
```

---

## 5. Combinación de Filtros

**GET** `http://localhost:8000/api/user-sessions?status=active&search=admin&per_page=10`

**Headers:**
```
Authorization: Bearer {tu_access_token}
Content-Type: application/json
```

---

## 6. Obtener Estadísticas de Sesiones

**GET** `http://localhost:8000/api/user-sessions/stats`

**Headers:**
```
Authorization: Bearer {tu_access_token}
Content-Type: application/json
```

**Respuesta Esperada (200 OK):**
```json
{
  "active_sessions": 15,
  "today_logins": 42,
  "week_logins": 156,
  "month_logins": 687,
  "total_sessions": 2340,
  "top_browsers": [
    {
      "browser": "Chrome",
      "count": 850
    },
    {
      "browser": "Firefox",
      "count": 320
    },
    {
      "browser": "Safari",
      "count": 180
    },
    {
      "browser": "Edge",
      "count": 95
    },
    {
      "browser": "Opera",
      "count": 32
    }
  ],
  "top_platforms": [
    {
      "platform": "Windows",
      "count": 920
    },
    {
      "platform": "Android",
      "count": 450
    },
    {
      "platform": "iOS",
      "count": 280
    },
    {
      "platform": "Mac OS",
      "count": 180
    },
    {
      "platform": "Linux",
      "count": 95
    }
  ],
  "device_types": [
    {
      "device_type": "desktop",
      "count": 1200
    },
    {
      "device_type": "mobile",
      "count": 980
    },
    {
      "device_type": "tablet",
      "count": 160
    }
  ]
}
```

---

## 7. Cerrar una Sesión Manualmente

**PUT** `http://localhost:8000/api/user-sessions/1/close`

**Headers:**
```
Authorization: Bearer {tu_access_token}
Content-Type: application/json
```

**Respuesta Esperada (200 OK):**
```json
{
  "message": "Sesión cerrada exitosamente",
  "session": {
    "id": 1,
    "user": {
      "id": 5,
      "name": "Juan Pérez",
      "email": "juan@example.com",
      "avatar": "http://localhost:8000/storage/avatars/user.jpg"
    },
    "ip_address": "192.168.1.100",
    "device_type": "desktop",
    "browser": "Chrome",
    "platform": "Windows",
    "location": null,
    "login_at": "2026-01-18 10:30:00",
    "logout_at": "2026-01-18 14:45:00",
    "is_active": false,
    "duration": "4 hours",
    "created_at": "2026-01-18 10:30:00"
  }
}
```

---

## Errores Comunes y Soluciones

### Error 401 - Unauthorized

**Respuesta:**
```json
{
  "message": "Unauthenticated."
}
```

**Soluciones:**
1. Verifica que el token esté presente en el header `Authorization: Bearer {token}`
2. Asegúrate de que el token no haya expirado
3. Intenta hacer login nuevamente para obtener un token fresco

---

### Error 403 - Forbidden

**Respuesta:**
```json
{
  "message": "No tienes permisos para acceder a esta información"
}
```

**Soluciones:**
1. Verifica que el usuario tenga rol `Admin` o `Super Admin`
2. Revisa en la base de datos la tabla `model_has_roles` para confirmar los roles del usuario

---

### Error 404 - Not Found

**Respuesta:**
```json
{
  "message": "No query results for model [App\\Models\\UserSession] 999"
}
```

**Soluciones:**
1. Verifica que el ID de sesión exista en la base de datos
2. Usa el endpoint de listado para obtener IDs válidos

---

## Pruebas con cURL

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"tu_password"}'
```

### Listar Sesiones
```bash
curl -X GET "http://localhost:8000/api/user-sessions" \
  -H "Authorization: Bearer {tu_token}" \
  -H "Content-Type: application/json"
```

### Obtener Estadísticas
```bash
curl -X GET "http://localhost:8000/api/user-sessions/stats" \
  -H "Authorization: Bearer {tu_token}" \
  -H "Content-Type: application/json"
```

### Cerrar Sesión
```bash
curl -X PUT "http://localhost:8000/api/user-sessions/1/close" \
  -H "Authorization: Bearer {tu_token}" \
  -H "Content-Type: application/json"
```

---

## Colección de Postman

Puedes importar esta configuración en Postman:

```json
{
  "info": {
    "name": "User Sessions API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Auth",
      "item": [
        {
          "name": "Login",
          "request": {
            "method": "POST",
            "header": [
              {
                "key": "Content-Type",
                "value": "application/json"
              }
            ],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"email\": \"admin@example.com\",\n  \"password\": \"password\"\n}"
            },
            "url": {
              "raw": "{{base_url}}/api/auth/login",
              "host": ["{{base_url}}"],
              "path": ["api", "auth", "login"]
            }
          }
        }
      ]
    },
    {
      "name": "User Sessions",
      "item": [
        {
          "name": "List Sessions",
          "request": {
            "method": "GET",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{access_token}}"
              }
            ],
            "url": {
              "raw": "{{base_url}}/api/user-sessions",
              "host": ["{{base_url}}"],
              "path": ["api", "user-sessions"]
            }
          }
        },
        {
          "name": "Get Stats",
          "request": {
            "method": "GET",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{access_token}}"
              }
            ],
            "url": {
              "raw": "{{base_url}}/api/user-sessions/stats",
              "host": ["{{base_url}}"],
              "path": ["api", "user-sessions", "stats"]
            }
          }
        },
        {
          "name": "Close Session",
          "request": {
            "method": "PUT",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{access_token}}"
              }
            ],
            "url": {
              "raw": "{{base_url}}/api/user-sessions/1/close",
              "host": ["{{base_url}}"],
              "path": ["api", "user-sessions", "1", "close"]
            }
          }
        }
      ]
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8000"
    },
    {
      "key": "access_token",
      "value": ""
    }
  ]
}
```

**Instrucciones:**
1. Copia el JSON anterior
2. En Postman: File > Import > Raw text
3. Pega el JSON y confirma
4. Configura las variables de entorno:
   - `base_url`: Tu URL del backend (ej: http://localhost:8000)
   - `access_token`: Se llenará automáticamente después del login si usas Tests en Postman

---

## Script de Test Automatizado (Postman)

Agrega este script en la pestaña "Tests" de la request de Login:

```javascript
// Guardar el token automáticamente
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("access_token", jsonData.access_token);
    console.log("Token guardado:", jsonData.access_token);
}

// Validar respuesta
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has access_token", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('access_token');
});
```

---

## Verificar Datos en la Base de Datos

```sql
-- Ver todas las sesiones
SELECT * FROM user_sessions ORDER BY login_at DESC LIMIT 10;

-- Ver sesiones activas
SELECT 
    us.id,
    u.name AS user_name,
    u.email,
    us.ip_address,
    us.browser,
    us.platform,
    us.device_type,
    us.login_at,
    us.is_active
FROM user_sessions us
JOIN users u ON us.user_id = u.id
WHERE us.is_active = true
ORDER BY us.login_at DESC;

-- Ver estadísticas básicas
SELECT 
    COUNT(*) AS total_sessions,
    COUNT(CASE WHEN is_active THEN 1 END) AS active_sessions,
    COUNT(CASE WHEN DATE(login_at) = CURRENT_DATE THEN 1 END) AS today_logins
FROM user_sessions;

-- Top navegadores
SELECT browser, COUNT(*) as count 
FROM user_sessions 
GROUP BY browser 
ORDER BY count DESC 
LIMIT 5;
```

---

## Tips para Depuración

1. **Ver logs de Laravel:**
```bash
tail -f storage/logs/laravel.log
```

2. **Verificar que las rutas estén registradas:**
```bash
php artisan route:list --path=user-sessions
```

3. **Verificar la tabla en la base de datos:**
```bash
php artisan tinker
>>> \App\Models\UserSession::count()
>>> \App\Models\UserSession::latest()->first()
```

4. **Limpiar caché si hay problemas:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```
