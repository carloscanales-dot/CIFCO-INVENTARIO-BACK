# Ejemplos de Implementación Frontend - Sistema de Sesiones

## Vue.js 3 (Composition API)

### 1. Composable para Sesiones

```javascript
// composables/useUserSessions.js
import { ref, computed } from 'vue'
import axios from 'axios'

export function useUserSessions() {
  const sessions = ref([])
  const stats = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  const activeSessions = computed(() => 
    sessions.value.filter(s => s.is_active)
  )

  const fetchSessions = async (page = 1, filters = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const params = {
        page,
        per_page: pagination.value.per_page,
        ...filters
      }
      
      const response = await axios.get('/api/user-sessions', { params })
      sessions.value = response.data.data
      pagination.value = {
        current_page: response.data.meta.current_page,
        last_page: response.data.meta.last_page,
        per_page: response.data.meta.per_page,
        total: response.data.meta.total
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar sesiones'
      console.error('Error fetching sessions:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchStats = async () => {
    try {
      const response = await axios.get('/api/user-sessions/stats')
      stats.value = response.data
    } catch (err) {
      console.error('Error fetching stats:', err)
    }
  }

  const closeSession = async (sessionId) => {
    try {
      await axios.put(`/api/user-sessions/${sessionId}/close`)
      // Actualizar la sesión en la lista local
      const session = sessions.value.find(s => s.id === sessionId)
      if (session) {
        session.is_active = false
        session.logout_at = new Date().toISOString()
      }
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cerrar sesión'
      return false
    }
  }

  return {
    sessions,
    stats,
    loading,
    error,
    pagination,
    activeSessions,
    fetchSessions,
    fetchStats,
    closeSession
  }
}
```

### 2. Componente de Vista de Sesiones

```vue
<!-- views/admin/UserSessionsView.vue -->
<template>
  <div class="user-sessions-container">
    <!-- Header con Estadísticas -->
    <div class="stats-grid" v-if="stats">
      <div class="stat-card">
        <h3>Sesiones Activas</h3>
        <p class="stat-number">{{ stats.active_sessions }}</p>
      </div>
      <div class="stat-card">
        <h3>Inicios Hoy</h3>
        <p class="stat-number">{{ stats.today_logins }}</p>
      </div>
      <div class="stat-card">
        <h3>Inicios Esta Semana</h3>
        <p class="stat-number">{{ stats.week_logins }}</p>
      </div>
      <div class="stat-card">
        <h3>Inicios Este Mes</h3>
        <p class="stat-number">{{ stats.month_logins }}</p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filters">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Buscar por nombre o email..."
        @input="debouncedSearch"
        class="search-input"
      />
      
      <select v-model="statusFilter" @change="handleFilterChange" class="filter-select">
        <option value="">Todas las sesiones</option>
        <option value="active">Solo activas</option>
      </select>
      
      <button @click="refreshData" class="btn-refresh">
        🔄 Actualizar
      </button>
    </div>

    <!-- Tabla de Sesiones -->
    <div class="table-container">
      <div v-if="loading" class="loading">Cargando...</div>
      
      <div v-else-if="error" class="error">
        {{ error }}
      </div>
      
      <table v-else class="sessions-table">
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Email</th>
            <th>IP Address</th>
            <th>Dispositivo</th>
            <th>Navegador</th>
            <th>Plataforma</th>
            <th>Inicio de Sesión</th>
            <th>Duración</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="session in sessions" :key="session.id">
            <td>
              <div class="user-info">
                <img
                  v-if="session.user.avatar"
                  :src="session.user.avatar"
                  :alt="session.user.name"
                  class="avatar"
                />
                <span>{{ session.user.name }}</span>
              </div>
            </td>
            <td>{{ session.user.email }}</td>
            <td>
              <code>{{ session.ip_address }}</code>
            </td>
            <td>
              <span class="device-badge" :class="`device-${session.device_type}`">
                {{ getDeviceIcon(session.device_type) }} {{ session.device_type }}
              </span>
            </td>
            <td>{{ session.browser }}</td>
            <td>{{ session.platform }}</td>
            <td>{{ formatDate(session.login_at) }}</td>
            <td>{{ session.duration }}</td>
            <td>
              <span
                class="status-badge"
                :class="session.is_active ? 'status-active' : 'status-inactive'"
              >
                {{ session.is_active ? '🟢 Activa' : '🔴 Inactiva' }}
              </span>
            </td>
            <td>
              <button
                v-if="session.is_active"
                @click="handleCloseSession(session.id)"
                class="btn-close"
                title="Cerrar sesión"
              >
                ❌ Cerrar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div class="pagination" v-if="pagination.last_page > 1">
      <button
        @click="goToPage(pagination.current_page - 1)"
        :disabled="pagination.current_page === 1"
        class="btn-page"
      >
        ← Anterior
      </button>
      
      <span class="page-info">
        Página {{ pagination.current_page }} de {{ pagination.last_page }}
        ({{ pagination.total }} resultados)
      </span>
      
      <button
        @click="goToPage(pagination.current_page + 1)"
        :disabled="pagination.current_page === pagination.last_page"
        class="btn-page"
      >
        Siguiente →
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useUserSessions } from '@/composables/useUserSessions'
import { debounce } from 'lodash-es' // o implementa tu propia función debounce

const {
  sessions,
  stats,
  loading,
  error,
  pagination,
  fetchSessions,
  fetchStats,
  closeSession
} = useUserSessions()

const searchQuery = ref('')
const statusFilter = ref('')

onMounted(() => {
  refreshData()
})

const refreshData = () => {
  fetchSessions(1, getFilters())
  fetchStats()
}

const getFilters = () => {
  const filters = {}
  if (searchQuery.value) filters.search = searchQuery.value
  if (statusFilter.value) filters.status = statusFilter.value
  return filters
}

const handleFilterChange = () => {
  fetchSessions(1, getFilters())
}

const debouncedSearch = debounce(() => {
  fetchSessions(1, getFilters())
}, 500)

const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchSessions(page, getFilters())
  }
}

const handleCloseSession = async (sessionId) => {
  if (confirm('¿Estás seguro de cerrar esta sesión?')) {
    const success = await closeSession(sessionId)
    if (success) {
      alert('Sesión cerrada exitosamente')
    }
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getDeviceIcon = (type) => {
  const icons = {
    desktop: '🖥️',
    mobile: '📱',
    tablet: '📲'
  }
  return icons[type] || '💻'
}
</script>

<style scoped>
.user-sessions-container {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
}

.stat-card h3 {
  margin: 0 0 10px 0;
  font-size: 14px;
  color: #666;
}

.stat-number {
  font-size: 32px;
  font-weight: bold;
  color: #333;
  margin: 0;
}

.filters {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.search-input,
.filter-select {
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.search-input {
  flex: 1;
  min-width: 200px;
}

.btn-refresh {
  padding: 10px 20px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-refresh:hover {
  background: #45a049;
}

.table-container {
  background: white;
  border-radius: 8px;
  overflow-x: auto;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sessions-table {
  width: 100%;
  border-collapse: collapse;
}

.sessions-table th {
  background: #f5f5f5;
  padding: 15px;
  text-align: left;
  font-weight: 600;
  color: #333;
  border-bottom: 2px solid #ddd;
}

.sessions-table td {
  padding: 12px 15px;
  border-bottom: 1px solid #eee;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.device-badge,
.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.device-desktop { background: #E3F2FD; color: #1976D2; }
.device-mobile { background: #F3E5F5; color: #7B1FA2; }
.device-tablet { background: #FFF3E0; color: #F57C00; }

.status-active { background: #E8F5E9; color: #2E7D32; }
.status-inactive { background: #FFEBEE; color: #C62828; }

.btn-close {
  padding: 6px 12px;
  background: #f44336;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.btn-close:hover {
  background: #d32f2f;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 20px;
  padding: 20px;
}

.btn-page {
  padding: 8px 16px;
  background: #2196F3;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-page:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.page-info {
  font-size: 14px;
  color: #666;
}

.loading,
.error {
  text-align: center;
  padding: 40px;
  font-size: 16px;
}

.error {
  color: #f44336;
}
</style>
```

## React (con hooks)

```javascript
// hooks/useUserSessions.js
import { useState, useCallback } from 'react'
import axios from 'axios'

export const useUserSessions = () => {
  const [sessions, setSessions] = useState([])
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [pagination, setPagination] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  const fetchSessions = useCallback(async (page = 1, filters = {}) => {
    setLoading(true)
    setError(null)
    
    try {
      const params = {
        page,
        per_page: pagination.per_page,
        ...filters
      }
      
      const response = await axios.get('/api/user-sessions', { params })
      setSessions(response.data.data)
      setPagination({
        current_page: response.data.meta.current_page,
        last_page: response.data.meta.last_page,
        per_page: response.data.meta.per_page,
        total: response.data.meta.total
      })
    } catch (err) {
      setError(err.response?.data?.message || 'Error al cargar sesiones')
    } finally {
      setLoading(false)
    }
  }, [pagination.per_page])

  const fetchStats = useCallback(async () => {
    try {
      const response = await axios.get('/api/user-sessions/stats')
      setStats(response.data)
    } catch (err) {
      console.error('Error fetching stats:', err)
    }
  }, [])

  const closeSession = useCallback(async (sessionId) => {
    try {
      await axios.put(`/api/user-sessions/${sessionId}/close`)
      setSessions(prev => 
        prev.map(session =>
          session.id === sessionId
            ? { ...session, is_active: false, logout_at: new Date().toISOString() }
            : session
        )
      )
      return true
    } catch (err) {
      setError(err.response?.data?.message || 'Error al cerrar sesión')
      return false
    }
  }, [])

  return {
    sessions,
    stats,
    loading,
    error,
    pagination,
    fetchSessions,
    fetchStats,
    closeSession
  }
}

// Componente UserSessionsView.jsx
import React, { useEffect, useState, useCallback } from 'react'
import { useUserSessions } from '../hooks/useUserSessions'
import { debounce } from 'lodash'

const UserSessionsView = () => {
  const {
    sessions,
    stats,
    loading,
    error,
    pagination,
    fetchSessions,
    fetchStats,
    closeSession
  } = useUserSessions()

  const [searchQuery, setSearchQuery] = useState('')
  const [statusFilter, setStatusFilter] = useState('')

  useEffect(() => {
    refreshData()
  }, [])

  const refreshData = () => {
    fetchSessions(1, getFilters())
    fetchStats()
  }

  const getFilters = () => {
    const filters = {}
    if (searchQuery) filters.search = searchQuery
    if (statusFilter) filters.status = statusFilter
    return filters
  }

  const debouncedSearch = useCallback(
    debounce(() => {
      fetchSessions(1, getFilters())
    }, 500),
    [searchQuery, statusFilter]
  )

  const handleSearchChange = (e) => {
    setSearchQuery(e.target.value)
    debouncedSearch()
  }

  const handleFilterChange = (e) => {
    setStatusFilter(e.target.value)
    fetchSessions(1, { ...getFilters(), status: e.target.value })
  }

  const handleCloseSession = async (sessionId) => {
    if (window.confirm('¿Estás seguro de cerrar esta sesión?')) {
      const success = await closeSession(sessionId)
      if (success) {
        alert('Sesión cerrada exitosamente')
      }
    }
  }

  const goToPage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
      fetchSessions(page, getFilters())
    }
  }

  return (
    <div className="user-sessions-container">
      {/* Stats Cards */}
      {stats && (
        <div className="stats-grid">
          <div className="stat-card">
            <h3>Sesiones Activas</h3>
            <p className="stat-number">{stats.active_sessions}</p>
          </div>
          <div className="stat-card">
            <h3>Inicios Hoy</h3>
            <p className="stat-number">{stats.today_logins}</p>
          </div>
          <div className="stat-card">
            <h3>Inicios Esta Semana</h3>
            <p className="stat-number">{stats.week_logins}</p>
          </div>
          <div className="stat-card">
            <h3>Inicios Este Mes</h3>
            <p className="stat-number">{stats.month_logins}</p>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="filters">
        <input
          type="text"
          placeholder="Buscar por nombre o email..."
          value={searchQuery}
          onChange={handleSearchChange}
          className="search-input"
        />
        
        <select value={statusFilter} onChange={handleFilterChange} className="filter-select">
          <option value="">Todas las sesiones</option>
          <option value="active">Solo activas</option>
        </select>
        
        <button onClick={refreshData} className="btn-refresh">
          🔄 Actualizar
        </button>
      </div>

      {/* Table */}
      {loading ? (
        <div className="loading">Cargando...</div>
      ) : error ? (
        <div className="error">{error}</div>
      ) : (
        <div className="table-container">
          <table className="sessions-table">
            <thead>
              <tr>
                <th>Usuario</th>
                <th>Email</th>
                <th>IP Address</th>
                <th>Dispositivo</th>
                <th>Navegador</th>
                <th>Plataforma</th>
                <th>Inicio</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {sessions.map(session => (
                <tr key={session.id}>
                  <td>
                    <div className="user-info">
                      {session.user.avatar && (
                        <img src={session.user.avatar} alt={session.user.name} className="avatar" />
                      )}
                      <span>{session.user.name}</span>
                    </div>
                  </td>
                  <td>{session.user.email}</td>
                  <td><code>{session.ip_address}</code></td>
                  <td>
                    <span className={`device-badge device-${session.device_type}`}>
                      {session.device_type}
                    </span>
                  </td>
                  <td>{session.browser}</td>
                  <td>{session.platform}</td>
                  <td>{new Date(session.login_at).toLocaleString('es-ES')}</td>
                  <td>
                    <span className={`status-badge ${session.is_active ? 'status-active' : 'status-inactive'}`}>
                      {session.is_active ? '🟢 Activa' : '🔴 Inactiva'}
                    </span>
                  </td>
                  <td>
                    {session.is_active && (
                      <button onClick={() => handleCloseSession(session.id)} className="btn-close">
                        ❌ Cerrar
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      {pagination.last_page > 1 && (
        <div className="pagination">
          <button
            onClick={() => goToPage(pagination.current_page - 1)}
            disabled={pagination.current_page === 1}
            className="btn-page"
          >
            ← Anterior
          </button>
          
          <span className="page-info">
            Página {pagination.current_page} de {pagination.last_page}
          </span>
          
          <button
            onClick={() => goToPage(pagination.current_page + 1)}
            disabled={pagination.current_page === pagination.last_page}
            className="btn-page"
          >
            Siguiente →
          </button>
        </div>
      )}
    </div>
  )
}

export default UserSessionsView
```

## Angular

```typescript
// services/user-sessions.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap } from 'rxjs/operators';

export interface UserSession {
  id: number;
  user: {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
  };
  ip_address: string;
  device_type: string;
  browser: string;
  platform: string;
  login_at: string;
  logout_at: string | null;
  is_active: boolean;
  duration: string;
}

export interface SessionStats {
  active_sessions: number;
  today_logins: number;
  week_logins: number;
  month_logins: number;
  total_sessions: number;
}

@Injectable({
  providedIn: 'root'
})
export class UserSessionsService {
  private readonly API_URL = '/api/user-sessions';
  private sessionsSubject = new BehaviorSubject<UserSession[]>([]);
  private statsSubject = new BehaviorSubject<SessionStats | null>(null);

  sessions$ = this.sessionsSubject.asObservable();
  stats$ = this.statsSubject.asObservable();

  constructor(private http: HttpClient) {}

  fetchSessions(page: number = 1, filters: any = {}): Observable<any> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', '15');

    if (filters.search) {
      params = params.set('search', filters.search);
    }
    if (filters.status) {
      params = params.set('status', filters.status);
    }

    return this.http.get<any>(this.API_URL, { params }).pipe(
      tap(response => {
        this.sessionsSubject.next(response.data);
      })
    );
  }

  fetchStats(): Observable<SessionStats> {
    return this.http.get<SessionStats>(`${this.API_URL}/stats`).pipe(
      tap(stats => {
        this.statsSubject.next(stats);
      })
    );
  }

  closeSession(sessionId: number): Observable<any> {
    return this.http.put(`${this.API_URL}/${sessionId}/close`, {}).pipe(
      tap(() => {
        const sessions = this.sessionsSubject.value;
        const updatedSessions = sessions.map(session =>
          session.id === sessionId
            ? { ...session, is_active: false, logout_at: new Date().toISOString() }
            : session
        );
        this.sessionsSubject.next(updatedSessions);
      })
    );
  }
}

// component: user-sessions.component.ts
import { Component, OnInit } from '@angular/core';
import { UserSessionsService, UserSession, SessionStats } from '../../services/user-sessions.service';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';

@Component({
  selector: 'app-user-sessions',
  templateUrl: './user-sessions.component.html',
  styleUrls: ['./user-sessions.component.css']
})
export class UserSessionsComponent implements OnInit {
  sessions: UserSession[] = [];
  stats: SessionStats | null = null;
  loading = false;
  error: string | null = null;
  
  searchQuery = '';
  statusFilter = '';
  
  pagination = {
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  };

  private searchSubject = new Subject<string>();

  constructor(private sessionsService: UserSessionsService) {}

  ngOnInit(): void {
    this.refreshData();
    
    // Setup search debounce
    this.searchSubject
      .pipe(debounceTime(500), distinctUntilChanged())
      .subscribe(searchValue => {
        this.fetchSessions(1);
      });
  }

  refreshData(): void {
    this.fetchSessions(1);
    this.fetchStats();
  }

  fetchSessions(page: number): void {
    this.loading = true;
    this.error = null;

    const filters: any = {};
    if (this.searchQuery) filters.search = this.searchQuery;
    if (this.statusFilter) filters.status = this.statusFilter;

    this.sessionsService.fetchSessions(page, filters).subscribe({
      next: (response) => {
        this.sessions = response.data;
        this.pagination = {
          current_page: response.meta.current_page,
          last_page: response.meta.last_page,
          per_page: response.meta.per_page,
          total: response.meta.total
        };
        this.loading = false;
      },
      error: (err) => {
        this.error = err.error?.message || 'Error al cargar sesiones';
        this.loading = false;
      }
    });
  }

  fetchStats(): void {
    this.sessionsService.fetchStats().subscribe({
      next: (stats) => {
        this.stats = stats;
      },
      error: (err) => {
        console.error('Error fetching stats:', err);
      }
    });
  }

  onSearchChange(value: string): void {
    this.searchQuery = value;
    this.searchSubject.next(value);
  }

  onFilterChange(): void {
    this.fetchSessions(1);
  }

  closeSession(sessionId: number): void {
    if (confirm('¿Estás seguro de cerrar esta sesión?')) {
      this.sessionsService.closeSession(sessionId).subscribe({
        next: () => {
          alert('Sesión cerrada exitosamente');
        },
        error: (err) => {
          alert(err.error?.message || 'Error al cerrar sesión');
        }
      });
    }
  }

  goToPage(page: number): void {
    if (page >= 1 && page <= this.pagination.last_page) {
      this.fetchSessions(page);
    }
  }
}
```

## Configuración de Axios (Interceptor para Token)

```javascript
// axios.config.js
import axios from 'axios';

// Configurar base URL
axios.defaults.baseURL = 'http://localhost:8000'; // Tu URL del backend

// Interceptor para agregar el token en cada request
axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token'); // o sessionStorage
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para manejar errores de autenticación
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expirado o inválido
      localStorage.removeItem('access_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default axios;
```

## Protección de Rutas

### Vue Router
```javascript
// router/index.js
const routes = [
  {
    path: '/admin/sessions',
    name: 'UserSessions',
    component: () => import('@/views/admin/UserSessionsView.vue'),
    meta: {
      requiresAuth: true,
      requiresAdmin: true
    }
  }
];

router.beforeEach((to, from, next) => {
  const user = JSON.parse(localStorage.getItem('user'));
  
  if (to.meta.requiresAuth && !user) {
    next('/login');
  } else if (to.meta.requiresAdmin) {
    const isAdmin = ['Super Admin', 'Admin'].includes(user?.role?.name);
    if (isAdmin) {
      next();
    } else {
      next('/unauthorized');
    }
  } else {
    next();
  }
});
```

### React Router
```javascript
// ProtectedRoute.jsx
import { Navigate } from 'react-router-dom';

const ProtectedRoute = ({ children, requiresAdmin = false }) => {
  const user = JSON.parse(localStorage.getItem('user'));
  
  if (!user) {
    return <Navigate to="/login" />;
  }
  
  if (requiresAdmin) {
    const isAdmin = ['Super Admin', 'Admin'].includes(user?.role?.name);
    if (!isAdmin) {
      return <Navigate to="/unauthorized" />;
    }
  }
  
  return children;
};

// En App.jsx o Routes
<Route
  path="/admin/sessions"
  element={
    <ProtectedRoute requiresAdmin={true}>
      <UserSessionsView />
    </ProtectedRoute>
  }
/>
```
