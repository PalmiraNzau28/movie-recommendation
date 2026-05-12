import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { MovieService, Movie } from '../../../core/services/movie.service';

@Component({
  selector: 'app-popular',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule],
  template: `
    <div class="container mx-auto px-4 py-8 animate-fade-in">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">{{ 'POPULAR_MOVIES' | translate }}</h1>
        <button (click)="refreshMovies()" class="btn-secondary px-4 py-2" [disabled]="loading">
          🔄 Atualizar
        </button>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <div *ngFor="let movie of movies" class="card hover:scale-105 transition-transform duration-300 cursor-pointer" [routerLink]="['/movies', movie.id]">
          <img [src]="movie.poster_url || 'https://via.placeholder.com/500x750?text=No+Poster'" [alt]="movie.title" class="w-full h-64 object-cover">
          <div class="p-4">
            <h3 class="font-semibold text-lg truncate">{{ movie.title }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">⭐ {{ movie.popularity || 'N/A' }}</p>
          </div>
        </div>
      </div>
      <div *ngIf="movies.length === 0 && !loading" class="text-center py-12"><p class="text-gray-500">Nenhum filme encontrado</p></div>
      <div *ngIf="loading" class="text-center py-12"><div class="animate-pulse text-purple-600">Carregando...</div></div>
    </div>
  `
})
export class PopularComponent implements OnInit {
  movies: Movie[] = [];
  loading = true;
  
  constructor(private movieService: MovieService) {}
  
  ngOnInit() {
    this.loadMovies();
  }

  loadMovies() {
    this.loading = true;
    // SEMPRE buscar da API sem cache
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
