import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { MovieService, Movie } from '../../core/services/movie.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule],
  template: `
    <div class="container mx-auto px-4 py-8 animate-fade-in">
      <div class="flex justify-between items-center mb-8">
        <div class="text-center flex-1">
          <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
            {{ 'POPULAR_MOVIES' | translate }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-2">Filmes populares do momento - atualizados a cada acesso</p>
        </div>
        <button (click)="refreshMovies()" class="btn-secondary px-4 py-2" [disabled]="loading">
          🔄 Atualizar
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <div *ngFor="let movie of movies" class="card hover:scale-105 transition-transform duration-300 cursor-pointer" [routerLink]="['/movies', movie.id]">
          <img [src]="movie.poster_url || 'https://via.placeholder.com/500x750?text=No+Poster'" 
               [alt]="movie.title"
               class="w-full h-64 object-cover">
          <div class="p-4">
            <h3 class="font-semibold text-lg truncate">{{ movie.title }}</h3>
            <div class="flex items-center justify-between mt-2">
              <span class="text-yellow-500">⭐ {{ movie.popularity || 'N/A' }}</span>
              <span class="text-sm text-gray-500 dark:text-gray-400">⭐ {{ movie.movie_average_rating || 0 }}/5</span>
            </div>
          </div>
        </div>
      </div>

      <div *ngIf="movies.length === 0 && !loading" class="text-center py-12">
        <p class="text-gray-500">Nenhum filme encontrado</p>
      </div>

      <div *ngIf="loading" class="text-center py-12">
        <div class="animate-pulse text-purple-600">Carregando filmes...</div>
      </div>
    </div>
  `
})
export class DashboardComponent implements OnInit {
  movies: Movie[] = [];
  loading = true;

  constructor(private movieService: MovieService) {}

  ngOnInit() {
    this.loadMovies();
  }

  loadMovies() {
    this.loading = true;
    // SEMPRE buscar da API sem cache (forceRefresh=true)
    this.movieService.getPopularMovies(20, true).subscribe({
      next: (response) => {
        if (response.success) {
          this.movies = response.data;
        }
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  refreshMovies() {
    this.loadMovies();
  }
}