import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, TranslateModule],
  template: `
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-md w-full space-y-8 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl animate-fade-in">
        <div>
          <h2 class="text-center text-3xl font-extrabold text-gray-900 dark:text-white">
            {{ 'LOGIN' | translate }}
          </h2>
          <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            {{ 'RATE_MORE_MOVIES' | translate }}
          </p>
        </div>
        
        <form (ngSubmit)="onSubmit()" class="mt-8 space-y-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ 'EMAIL' | translate }}</label>
              <input type="email" [(ngModel)]="email" name="email" required class="input mt-1" [class.input-error]="error">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ 'PASSWORD' | translate }}</label>
              <input type="password" [(ngModel)]="password" name="password" required class="input mt-1" [class.input-error]="error">
            </div>
          </div>

          <div *ngIf="errorMessage" class="text-red-600 text-sm text-center bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
            {{ errorMessage }}
          </div>

          <button type="submit" [disabled]="loading" class="btn-primary w-full flex justify-center">
            <span *ngIf="!loading">{{ 'LOGIN' | translate }}</span>
            <span *ngIf="loading" class="animate-pulse">Carregando...</span>
          </button>

          <div class="text-center">
            <a routerLink="/register" class="text-purple-600 hover:text-purple-500 text-sm">
              {{ 'REGISTER' }}
            </a>
          </div>
        </form>
      </div>
    </div>
  `
})
export class LoginComponent {
  email = '';
  password = '';
  loading = false;
  error = false;
  errorMessage = '';

  constructor(private authService: AuthService, private router: Router) {}

  onSubmit() {
    if (!this.email || !this.password) {
      this.errorMessage = 'Preencha todos os campos';
      return;
    }

    this.loading = true;
    this.error = false;
    this.errorMessage = '';

    this.authService.login(this.email, this.password).subscribe({
      next: (response) => {
        if (response.success) {
          this.authService.checkSession();
          this.router.navigate(['/']);
        } else {
          this.errorMessage = response.message || 'Erro ao fazer login';
          this.error = true;
        }
        this.loading = false;
      },
      error: (err) => {
        this.errorMessage = err.error?.message || 'Erro de conexão com o servidor';
        this.error = true;
        this.loading = false;
      }
    });
  }
}
