import { Routes } from '@angular/router';
import { AuthGuard } from './core/guards/auth.guard';
import { MainLayoutComponent } from './layouts/main-layout/main-layout.component';

export const routes: Routes = [
  {
    path: '',
    component: MainLayoutComponent,
    canActivate: [AuthGuard],
    children: [
      { path: '', loadComponent: () => import('./features/dashboard/dashboard.component').then(m => m.DashboardComponent) },
      { path: 'movies/popular', loadComponent: () => import('./features/movies/popular/popular.component').then(m => m.PopularComponent) },
      { path: 'movies/:id', loadComponent: () => import('./features/movies/details/details.component').then(m => m.DetailsComponent) },
      { path: 'my-ratings', loadComponent: () => import('./features/ratings/my-ratings/my-ratings.component').then(m => m.MyRatingsComponent) },
      { path: 'recommendations', loadComponent: () => import('./features/recommendations/recommendations.component').then(m => m.RecommendationsComponent) }
    ]
  },
  { path: 'login', loadComponent: () => import('./features/auth/login/login.component').then(m => m.LoginComponent) },
  { path: 'register', loadComponent: () => import('./features/auth/register/register.component').then(m => m.RegisterComponent) },
  { path: '**', redirectTo: '' }
];
