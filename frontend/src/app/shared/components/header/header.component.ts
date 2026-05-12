import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { TranslateModule, TranslateService } from '@ngx-translate/core';
import { AuthService } from '../../../core/services/auth.service';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterLinkActive, TranslateModule],
  template: `
    <header class="sticky top-0 z-50 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <nav class="container mx-auto px-4 py-3 flex items-center justify-between">
        <a routerLink="/" class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
          {{ 'APP_NAME' | translate }}
        </a>

        <div class="hidden md:flex items-center space-x-6" *ngIf="authService.isAuthenticated()">
          <a routerLink="/" routerLinkActive="text-purple-600" [routerLinkActiveOptions]="{exact:true}" class="hover:text-purple-600 transition">{{ 'HOME' | translate }}</a>
          <a routerLink="/movies/popular" routerLinkActive="text-purple-600" class="hover:text-purple-600 transition">{{ 'MOVIES' | translate }}</a>
          <a routerLink="/my-ratings" routerLinkActive="text-purple-600" class="hover:text-purple-600 transition">{{ 'MY_RATINGS' | translate }}</a>
          <a routerLink="/recommendations" routerLinkActive="text-purple-600" class="hover:text-purple-600 transition">{{ 'RECOMMENDATIONS' | translate }}</a>
        </div>

        <div class="flex items-center gap-4">
          <button (click)="toggleDarkMode()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <span *ngIf="!isDarkMode()">🌙</span>
            <span *ngIf="isDarkMode()">☀️</span>
          </button>

          <button (click)="toggleLanguage()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition font-semibold">
            {{ currentLang() === 'pt' ? 'EN' : 'PT' }}
          </button>

          <div *ngIf="authService.isAuthenticated(); else authButtons" class="relative">
            <button (click)="showUserMenu = !showUserMenu" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
              <span class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 flex items-center justify-center text-white font-bold">
                {{ authService.currentUser()?.name?.charAt(0) || 'U' }}
              </span>
              <span>{{ authService.currentUser()?.name }}</span>
            </button>
            
            <div *ngIf="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-2">
              <button (click)="authService.logout()" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                {{ 'LOGOUT' | translate }}
              </button>
            </div>
          </div>
          
          <ng-template #authButtons>
            <div class="flex gap-2">
              <a routerLink="/login" class="px-4 py-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950 rounded-lg transition">{{ 'LOGIN' | translate }}</a>
              <a routerLink="/register" class="btn-primary">{{ 'REGISTER' | translate }}</a>
            </div>
          </ng-template>
        </div>
      </nav>
    </header>
  `
})
export class HeaderComponent {
  isDarkMode = signal(false);
  currentLang = signal('pt');
  showUserMenu = false;

  constructor(public authService: AuthService, private translate: TranslateService, private http: HttpClient) {
    this.isDarkMode.set(localStorage.getItem('darkMode') === 'true');
    this.currentLang.set(localStorage.getItem('language') || 'pt');
    this.applyTheme();
    this.loadTranslations();
  }

  private async loadTranslations() {
    try {
      const translations = await this.http.get<{[key: string]: string}>(`./assets/i18n/${this.currentLang()}.json`).toPromise();
      if (translations) {
        this.translate.setTranslation(this.currentLang(), translations);
        this.translate.use(this.currentLang());
      }
    } catch (error) {
      console.error('Error loading translations:', error);
    }
  }

  toggleDarkMode() {
    this.isDarkMode.update(v => !v);
    localStorage.setItem('darkMode', String(this.isDarkMode()));
    this.applyTheme();
  }

  private applyTheme() {
    if (this.isDarkMode()) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }

  async toggleLanguage() {
    const newLang = this.currentLang() === 'pt' ? 'en' : 'pt';
    this.currentLang.set(newLang);
    localStorage.setItem('language', newLang);
    await this.loadTranslations();
  }
}
