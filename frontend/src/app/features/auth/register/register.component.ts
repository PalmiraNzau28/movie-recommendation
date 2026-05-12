import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, TranslateModule],
  template: `
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800 py-12 px-4">
      <div class="max-w-md w-full bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-2xl">
        <h2 class="text-center text-3xl font-bold mb-8">Criar Conta</h2>
        
        <form (ngSubmit)="onSubmit()">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Nome</label>
            <input type="text" [(ngModel)]="name" name="name" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700" required>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" [(ngModel)]="email" name="email" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700" required>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Senha</label>
            <input type="password" [(ngModel)]="password" name="password" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700" required>
            <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres, maiúscula, minúscula, número e caractere especial</p>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-1">Confirmar Senha</label>
            <input type="password" [(ngModel)]="confirmPassword" name="confirmPassword" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700" required>
          </div>

          <div *ngIf="errorMessage" class="text-red-600 text-sm text-center bg-red-50 p-3 rounded-lg mb-4">
            {{ errorMessage }}
          </div>

          <div *ngIf="successMessage" class="text-green-600 text-sm text-center bg-green-50 p-3 rounded-lg mb-4">
            {{ successMessage }}
          </div>

          <button type="submit" [disabled]="loading" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">
            {{ loading ? 'Carregando...' : 'Registrar' }}
          </button>

          <div class="text-center mt-4">
            <a routerLink="/login" class="text-purple-600">Já tem conta? Entrar</a>
          </div>
        </form>
      </div>
    </div>
  `
})
export class RegisterComponent {
  name = '';
  email = '';
  password = '';
  confirmPassword = '';
  loading = false;
  errorMessage = '';
  successMessage = '';

  constructor(private authService: AuthService, private router: Router) {}

  onSubmit() {
    if (!this.name || !this.email || !this.password || !this.confirmPassword) {
      this.errorMessage = 'Preencha todos os campos';
      return;
    }

    if (this.password !== this.confirmPassword) {
      this.errorMessage = 'As senhas não coincidem';
      return;
    }

    this.loading = true;
    this.errorMessage = '';
    this.successMessage = '';

    this.authService.register(this.name, this.email, this.password, this.confirmPassword).subscribe({
      next: (response: any) => {
        console.log('Resposta do servidor:', response);
        if (response.success) {
          this.successMessage = 'Cadastro realizado! Redirecionando...';
          this.router.navigate(['/']);
        } else {
          this.errorMessage = response.message || 'Erro ao cadastrar';
          if (response.errors) {
            this.errorMessage = Object.values(response.errors).join(', ');
          }
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erro completo:', err);
        this.errorMessage = err.message || 'Erro de conexão com o servidor';
        this.loading = false;
      }
    });
  }
}