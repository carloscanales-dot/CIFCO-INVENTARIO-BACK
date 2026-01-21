# Sistema de Registro de Sesiones de Usuarios

## Descripción
Sistema implementado para registrar automáticamente las sesiones de usuarios que inician sesión desde el frontend. Los administradores pueden ver y gestionar estas sesiones desde el panel de administración.

## Estructura de la Base de Datos

### Tabla: `user_sessions`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGSERIAL | ID único de la sesión |
| user_id | BIGINT | ID del usuario (FK a users) |
| ip_address | VARCHAR(45) | Dirección IP del usuario |
| user_agent | TEXT | User agent completo del navegador |
| device_type | VARCHAR(50) | Tipo de dispositivo (mobile, tablet, desktop) |
| browser | VARCHAR(50) | Navegador utilizado |
| platform | VARCHAR(50) | Sistema operativo |
| location | VARCHAR(100) | Ubicación (opcional, para futuras implementaciones) |
| login_at | TIMESTAMP | Fecha y hora de inicio de sesión |
| logout_at | TIMESTAMP | Fecha y hora de cierre de sesión |
| is_active | BOOLEAN | Indica si la sesión está activa |
| token_id | VARCHAR(255) | Últimos 10 caracteres del token JWT |
| created_at | TIMESTAMP | Fecha de creación del registro |
| updated_at | TIMESTAMP | Fecha de última actualización |

## Endpoints API

### 1. Listar Sesiones
**GET** `/api/user-sessions`

**Permisos:** Solo Super Admin y Admin

**Parámetros de consulta:**
- `per_page` (int, opcional): Cantidad de resultados por página (default: 15)
- `search` (string, opcional): Buscar por nombre o email del usuario
- `status` (string, opcional): 'active' para sesiones activas, omitir para todas

**Ejemplo de request:**
```http
GET /api/user-sessions?per_page=20&status=active&search=juan
Authorization: Bearer {token}
```

**Ejemplo de respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 5,
        "name": "Juan Pérez",
        "email": "juan@example.com",
        "avatar": "http://localhost/storage/avatars/user.jpg"
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
  "links": {...},
  "meta": {...}
}
```

### 2. Estadísticas de Sesiones
**GET** `/api/user-sessions/stats`

**Permisos:** Solo Super Admin y Admin

**Ejemplo de respuesta:**
```json
{
  "active_sessions": 15,
  "today_logins": 42,
  "week_logins": 156,
  "month_logins": 687,
  "total_sessions": 2340,
  "top_browsers": [
    {"browser": "Chrome", "count": 850},
    {"browser": "Firefox", "count": 320},
    {"browser": "Safari", "count": 180}
  ],
  "top_platforms": [
    {"platform": "Windows", "count": 920},
    {"platform": "Android", "count": 450},
    {"platform": "iOS", "count": 280}
  ],
  "device_types": [
    {"device_type": "desktop", "count": 1200},
    {"device_type": "mobile", "count": 980},
    {"device_type": "tablet", "count": 160}
  ]
}
```

### 3. Cerrar Sesión Manualmente
**PUT** `/api/user-sessions/{id}/close`

**Permisos:** Solo Super Admin y Admin

**Ejemplo de respuesta:**
```json
{
  "message": "Sesión cerrada exitosamente",
  "session": {
    "id": 1,
    "user": {...},
    "is_active": false,
    "logout_at": "2026-01-18 14:45:00"
  }
}
```

## Funcionamiento Automático

### Login
Cuando un usuario inicia sesión mediante `POST /api/auth/login`, automáticamente se registra una nueva entrada en `user_sessions` con:
- Información del usuario
- IP address
- User agent y detalles del dispositivo/navegador
- Timestamp de login
- Estado activo

### Logout
Cuando un usuario cierra sesión mediante `POST /api/auth/logout`, automáticamente se actualiza su sesión activa con:
- Estado inactivo (`is_active = false`)
- Timestamp de logout

## Implementación en el Frontend

### 1. Vista de Administración (Solo Administradores)

Crear una vista en tu frontend (por ejemplo: `UserSessionsView.vue` o similar) que:

1. **Verificar permisos:** Asegúrate de que solo usuarios con rol `Super Admin` o `Admin` puedan acceder:
```javascript
// Ejemplo en Vue.js
computed: {
  isAdmin() {
    return this.user.role.name === 'Super Admin' || this.user.role.name === 'Admin'
  }
}
```

2. **Obtener lista de sesiones:**
```javascript
async fetchSessions(page = 1, filters = {}) {
  try {
    const params = new URLSearchParams({
      page: page,
      per_page: 15,
      ...filters
    });

    const response = await fetch(`/api/user-sessions?${params}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error fetching sessions:', error);
  }
}
```

3. **Obtener estadísticas:**
```javascript
async fetchStats() {
  const response = await fetch('/api/user-sessions/stats', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  return await response.json();
}
```

4. **Cerrar sesión manualmente:**
```javascript
async closeSession(sessionId) {
  const response = await fetch(`/api/user-sessions/${sessionId}/close`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  return await response.json();
}
```

### 2. Ejemplo de Tabla para Mostrar Sesiones

```html
<table>
  <thead>
    <tr>
      <th>Usuario</th>
      <th>Email</th>
      <th>IP Address</th>
      <th>Dispositivo</th>
      <th>Navegador</th>
      <th>Plataforma</th>
      <th>Inicio de Sesión</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <tr v-for="session in sessions" :key="session.id">
      <td>{{ session.user.name }}</td>
      <td>{{ session.user.email }}</td>
      <td>{{ session.ip_address }}</td>
      <td>{{ session.device_type }}</td>
      <td>{{ session.browser }}</td>
      <td>{{ session.platform }}</td>
      <td>{{ session.login_at }}</td>
      <td>
        <span :class="session.is_active ? 'badge-success' : 'badge-secondary'">
          {{ session.is_active ? 'Activa' : 'Inactiva' }}
        </span>
      </td>
      <td>
        <button
          v-if="session.is_active"
          @click="closeSession(session.id)"
          class="btn-danger">
          Cerrar Sesión
        </button>
      </td>
    </tr>
  </tbody>
</table>
```

### 3. Protección de Rutas en el Frontend

Asegúrate de proteger la ruta de administración de sesiones:

```javascript
// Ejemplo en Vue Router
{
  path: '/admin/sessions',
  name: 'UserSessions',
  component: () => import('@/views/admin/UserSessionsView.vue'),
  meta: {
    requiresAuth: true,
    requiresAdmin: true // Solo para administradores
  }
}

// Guard de navegación
router.beforeEach((to, from, next) => {
  if (to.meta.requiresAdmin) {
    const user = store.state.user;
    if (user.role.name === 'Super Admin' || user.role.name === 'Admin') {
      next();
    } else {
      next('/unauthorized');
    }
  } else {
    next();
  }
});
```

## Archivos Creados/Modificados

### Nuevos Archivos:
1. `/app/Models/UserSession.php` - Modelo Eloquent
2. `/app/Http/Controllers/UserSessionController.php` - Controlador
3. `/app/Http/Resources/UserSessionResource.php` - Resource para formatear respuestas

### Archivos Modificados:
1. `/app/Http/Controllers/AuthController.php` - Agregados métodos `logUserSession()` y `closeUserSession()`
2. `/routes/api.php` - Agregadas rutas para user-sessions

### Base de Datos:
- Tabla `user_sessions` creada con todos los campos necesarios e índices optimizados

## Notas Importantes

1. **Privacidad:** Los datos de sesión contienen información sensible. Asegúrate de que solo administradores puedan acceder.

2. **Almacenamiento:** Las sesiones se guardan indefinidamente. Considera implementar un sistema de limpieza periódica de sesiones antiguas.

3. **Detección de Dispositivos:** La detección de navegador/dispositivo/plataforma es básica usando regex. Para mayor precisión, considera usar una librería como `jenssegers/agent`.

4. **Token ID:** Solo se almacenan los últimos 10 caracteres del token JWT para referencia, no el token completo por seguridad.

5. **Sesiones Múltiples:** Un usuario puede tener múltiples sesiones activas simultáneamente (diferentes dispositivos/navegadores).

## Mejoras Futuras Sugeridas

1. **Geolocalización:** Integrar servicio de geolocalización por IP (GeoIP2, ipapi.co)
2. **Notificaciones:** Alertar a usuarios sobre nuevos inicios de sesión
3. **Limpieza automática:** Job programado para limpiar sesiones antiguas
4. **Bloqueo de dispositivos:** Permitir a usuarios bloquear dispositivos específicos
5. **Exportación:** Permitir exportar historial de sesiones a Excel/PDF
