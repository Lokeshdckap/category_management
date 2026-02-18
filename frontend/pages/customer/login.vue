<template>
  <div class="auth-page">
    <div class="auth-card">
      <h1 class="auth-title">Welcome Back</h1>
      <p class="auth-subtitle">Login to your account</p>

      <form @submit.prevent="handleLogin" class="auth-form">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input 
            id="email" 
            v-model="form.email" 
            type="email" 
            placeholder="john@example.com" 
            required 
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input 
            id="password" 
            v-model="form.password" 
            type="password" 
            placeholder="••••••••" 
            required 
          />
        </div>

        <button type="submit" class="auth-btn" :disabled="loading">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>

        <div v-if="message" :class="['message', messageType]">
          {{ message }}
        </div>

        <p class="auth-footer">
          Don't have an account? 
          <NuxtLink to="/customer/register">Register here</NuxtLink>
        </p>
      </form>
    </div>

    <!-- Notification Toast (Simplified) -->
    <div v-if="showToast" class="toast-overlay">
        <div class="toast warning">
            <div class="toast-icon">⚠️</div>
            <div class="toast-content">{{ toastMessage }}</div>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const form = ref({
  email: '',
  password: ''
});

const loading = ref(false);
const message = ref('');
const messageType = ref('');

const showToast = ref(false);
const toastMessage = ref('');

const { setAuth } = useAuth();

const handleLogin = async () => {
  loading.value = true;
  message.value = '';
  showToast.value = false;
  
  try {
    const response = await fetch('http://127.0.0.1:8000/api/customer/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    const data = await response.json();

    if (response.ok) {
      // Store token and redirect using useAuth
      setAuth(data.data.customer, data.data.token);
      
      message.value = 'Login successful!';
      messageType.value = 'success';
      
      setTimeout(() => {
        navigateTo('/');
      }, 1000);
    } else {
        if (response.status === 403) {
            // Case: account under verification
            toastMessage.value = data.message || 'Account under verification once verified we will notify you';
            showToast.value = true;
            setTimeout(() => { showToast.value = false; }, 6000);
        } else {
            message.value = data.message || 'Login failed. Please check your credentials.';
            messageType.value = 'error';
        }
    }
  } catch (error) {
    message.value = 'An error occurred. Please check your connection.';
    messageType.value = 'error';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 100px);
  padding: 20px;
  position: relative;
}

.auth-card {
  background: white;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
  width: 100%;
  max-width: 450px;
  border: 1px solid #eee;
}

.auth-title {
  font-size: 2rem;
  font-weight: 800;
  text-align: center;
  margin: 0 0 10px;
  color: #2c3e50;
}

.auth-subtitle {
  text-align: center;
  color: #888;
  margin-bottom: 30px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-weight: 600;
  font-size: 0.9rem;
  color: #2c3e50;
}

.form-group input {
  padding: 14px;
  border-radius: 10px;
  border: 1px solid #ddd;
  font-size: 1rem;
  transition: border-color 0.3s, box-shadow 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
}

.auth-btn {
  background: #3498db;
  color: white;
  padding: 16px;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.3s, transform 0.2s;
  margin-top: 10px;
}

.auth-btn:hover:not(:disabled) {
  background: #2980b9;
  transform: translateY(-2px);
}

.auth-btn:disabled {
  background: #bdc3c7;
  cursor: not-allowed;
}

.auth-footer {
  text-align: center;
  margin-top: 25px;
  font-size: 0.95rem;
  color: #666;
}

.auth-footer a {
  color: #3498db;
  font-weight: 700;
}

.message {
  padding: 15px;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  text-align: center;
}

.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

/* Toast Styles */
.toast-overlay {
    position: fixed;
    top: 30px;
    right: 30px;
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
}

.toast {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    background: white;
    min-width: 300px;
}

.toast.warning {
    border-left: 6px solid #f39c12;
}

.toast-icon {
    font-size: 1.5rem;
    margin-right: 15px;
}

.toast-content {
    font-weight: 600;
    color: #2c3e50;
    line-height: 1.4;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>
