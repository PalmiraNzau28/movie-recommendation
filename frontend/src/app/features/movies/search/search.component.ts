import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { MovieService, Movie } from '../../../core/services/movie.service';

@Component({
  selector: 'app-search',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, TranslateModule],
  template: `
    <div class="container mx-auto px-4 py-8 animate-fade-in">
      <div class="max-w-2xl mx-auto mb-8">
        <div class="flex gap-2">
          <input type="text" [(ngModel)]="query" (keyup.enter)="search()" placeholder="{{ 'SEARCH' | translate }}" class="input flex-1">
          <button (click)="search()" class="btn-primary">🔍</button>
        </div>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <div *ngFor="let movie of movies" class="card hover:scale-105 transition-transform duration-300 cursor-pointer" [routerLink]="['/movies', movie.id]">
          <img [src]="movie.poster_url || 'https://via.placeholder.com/500x750?text=No+Poster'" [alt]="movie.title" class="w-full h-64 object-cover">
          <div class="p-4"><h3 class="font-semibold text-lg truncate">{{ movie.title }}</h3></div>
        </div>
      </div>
      <div *ngIf="searched && movies.length === 0 && !loading" class="text-center py-12"><p class="text-gray-500">Nenhum resultado encontrado</p></div>
    </div>
  `
})
export class SearchComponent {
  query = '';
  movies: Movie[] = [];
  loading = false;
  searched = false;
  constructor(private movieService: MovieService) {}
  search() {
    if (!this.query.trim()) return;
    this.loading = true;
    this.searched = true;
    this.movieService.searchMovies(this.query).subscribe({
      next: (response) => { if (response.success) { this.movies = response.data; } this.loading = false; },
      error: () => { this.loading = false; }
    });
  }
}
