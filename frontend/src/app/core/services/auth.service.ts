import { Injectable, signal } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Router } from '@angular/router';
import { catchError, tap, throwError } from 'rxjs';

interface User {
  id: number;
  name: string;
  email: string;
}

interface AuthResponse {
  success: boolean;
  message: string;
  data?: User;
  errors?: any;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private apiUrl = 'http://localhost:8000/api';
  public currentUser = signal<User | null>(null);
  public isAuthenticated = signal<boolean>(false);

  constructor(private http: HttpClient, private router: Router) {
    this.checkSession();
  }

  private setAuthState(user: User | null) {
    this.currentUser.set(user);
    this.isAuthenticated.set(!!user);
  }

  register(name: string, email: string, password: string, confirmPassword: string) {
    const body = { name, email, password, confirm_password: confirmPassword };
    console.log('Enviando registro:', body);
    
    return this.http.post<AuthResponse>(
      `${this.apiUrl}/auth.php?action=register`,
      body,
      { withCredentials: true }
    ).pipe(
      tap((response) => {
        if (response.success && response.data) {
          this.setAuthState(response.data);
        }
      }),
      catchError((error: HttpErrorResponse) => {
        console.error('Erro na requisição:', error);
        if (error.error && typeof error.error === 'string') {
          try {
            const parsedError = JSON.parse(error.error);
            return throwError(() => parsedError);
          } catch (e) {
            return throwError(() => ({ message: error.error || 'Erro de conexão' }));
          }
        }
        return throwError(() => error.error || { message: 'Erro de conexão com o servidor' });
      })
    );
  }

  login(email: string, password: string) {
    return this.http.post<AuthResponse>(
      `${this.apiUrl}/auth.php?action=login`,
      { email, password },
      { withCredentials: true }
    ).pipe(
      tap((response) => {
        if (response.success && response.data) {
          this.setAuthState(response.data);
        }
      }),
      catchError((error: HttpErrorResponse) => {
        console.error('Erro na requisição de login:', error);
        if (error.error && typeof error.error === 'string') {
          try {
            const parsedError = JSON.parse(error.error);
            return throwError(() => parsedError);
          } catch (e) {
            return throwError(() => ({ message: error.error || 'Erro de conexão' }));
          }
        }
        return throwError(() => error.error || { message: 'Erro de conexão com o servidor' });
      })
    );
  }

  logout() {
    this.http.post<AuthResponse>(
      `${this.apiUrl}/auth.php?action=logout`,
      {},
      { withCredentials: true }
    ).subscribe(() => {
      this.currentUser.set(null);
      this.isAuthenticated.set(false);
      this.router.navigate(['/login']);
    });
  }

  checkSession() {
    this.http.get<AuthResponse>(
      `${this.apiUrl}/auth.php?action=me`,
      { withCredentials: true }
    ).subscribe({
      next: (response) => {
        if (response.success && response.data) {
          this.currentUser.set(response.data);
          this.isAuthenticated.set(true);
        }
      },
      error: () => {
        this.currentUser.set(null);
        this.isAuthenticated.set(false);
      }
    });
  }
}