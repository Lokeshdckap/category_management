<template>
  <nav class="navbar">
    <div class="container navbar-container">
      <NuxtLink to="/" class="brand">
        <span class="brand-text">STORE</span>
      </NuxtLink>

      <div class="nav-links">
        <NuxtLink to="/" class="nav-item">Home</NuxtLink>
      </div>

      <div class="auth-section">
        <template v-if="isLoggedIn && user">
          <div class="user-dropdown" @click="toggleDropdown" v-click-outside="closeDropdown">
            <div class="user-info">
              <div class="user-avatar">{{ user.name.charAt(0).toUpperCase() }}</div>
              <span class="user-name">{{ user.name }}</span>
              <span class="dropdown-icon" :class="{ 'open': isDropdownOpen }">▼</span>
            </div>
            
            <div v-if="isDropdownOpen" class="dropdown-menu">
              <div class="dropdown-header">
                <p class="user-email">{{ user.email }}</p>
              </div>
              <div class="dropdown-divider"></div>
              <button @click="handleLogout" class="dropdown-item logout-btn">
                <span class="icon">Logout</span>
              </button>
            </div>
          </div>
        </template>
        <template v-else>
          <div class="guest-links">
            <NuxtLink to="/customer/login" class="nav-item">Login</NuxtLink>
            <NuxtLink to="/customer/register" class="nav-item register-btn">Register</NuxtLink>
          </div>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const { user, isLoggedIn, logout } = useAuth();
const isDropdownOpen = ref(false);

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
};

const closeDropdown = () => {
  isDropdownOpen.value = false;
};

const handleLogout = () => {
  logout();
  closeDropdown();
};

// Simple click-outside directive or logic
// For a production app, I'd use a more robust solution or a library
const vClickOutside = {
  mounted(el: any, binding: any) {
    el.clickOutsideEvent = (event: any) => {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value();
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el: any) {
    document.removeEventListener('click', el.clickOutsideEvent);
  },
};
</script>

<style scoped>
.navbar {
  background: white;
  height: 80px;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  position: sticky;
  top: 0;
  z-index: 100;
}

.navbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.brand {
  font-weight: 900;
  font-size: 1.5rem;
  letter-spacing: 1px;
  color: #2c3e50;
  display: flex;
  align-items: center;
}

.brand-text {
  background: linear-gradient(135deg, #3498db, #2c3e50);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.nav-links {
  display: flex;
  gap: 30px;
}

.nav-item {
  font-weight: 600;
  color: #666;
  transition: color 0.3s;
  font-size: 1rem;
}

.nav-item:hover {
  color: #3498db;
}

.auth-section {
  display: flex;
  align-items: center;
}

.guest-links {
  display: flex;
  align-items: center;
  gap: 20px;
}

.register-btn {
  background: #3498db;
  color: white !important;
  padding: 8px 20px;
  border-radius: 8px;
  transition: background 0.3s;
}

.register-btn:hover {
  background: #2980b9;
}

.user-dropdown {
  position: relative;
  cursor: pointer;
  padding: 5px 10px;
  border-radius: 12px;
  transition: background 0.3s;
}

.user-dropdown:hover {
  background: #f8fafc;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  background: #3498db;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.user-name {
  font-weight: 600;
  color: #2c3e50;
}

.dropdown-icon {
  font-size: 0.8rem;
  color: #94a3b8;
  transition: transform 0.3s;
}

.dropdown-icon.open {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background: white;
  min-width: 200px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  border: 1px solid #f1f5f9;
  padding: 8px;
  animation: slideDown 0.2s ease-out;
}

.dropdown-header {
  padding: 12px;
}

.user-email {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0;
}

.dropdown-divider {
  height: 1px;
  background: #f1f5f9;
  margin: 8px 0;
}

.dropdown-item {
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: none;
  border-radius: 8px;
  text-align: left;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: #475569;
  transition: background 0.2s, color 0.2s;
}

.dropdown-item:hover {
  background: #f8fafc;
  color: #2c3e50;
}

.logout-btn:hover {
  background: #fff1f2;
  color: #e11d48;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
