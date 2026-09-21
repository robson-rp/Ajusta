import type { RouteRecordRaw } from 'vue-router'

const AuthLayout = () => import('../../layouts/AuthLayout.vue')
const LoginView = () => import('./views/LoginView.vue')
const ForgotPasswordView = () => import('./views/ForgotPasswordView.vue')
const ResetPasswordView = () => import('./views/ResetPasswordView.vue')
const RegisterWithInvitationView = () => import('./views/RegisterWithInvitationView.vue')
const SignupView = () => import('./views/SignupView.vue')

export const authRoutes: RouteRecordRaw[] = [
  {
    path: '/login',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'login',
        component: LoginView,
        meta: {
          requiresAuth: false,
          title: 'Login',
        },
      },
      {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordView,
        meta: {
          requiresAuth: false,
          title: 'Forgot Password',
        },
      },
      {
        path: '/reset-password/:token',
        name: 'reset-password',
        component: ResetPasswordView,
        meta: {
          requiresAuth: false,
          title: 'Reset Password',
        },
      },
      {
        path: '/signup',
        name: 'signup',
        component: SignupView,
        meta: {
          requiresAuth: false,
          title: 'Signup',
        },
      },
      {
        path: '/register',
        name: 'register-with-invitation',
        component: RegisterWithInvitationView,
        meta: {
          requiresAuth: false,
          title: 'Register',
        },
      },
    ],
  },
]
