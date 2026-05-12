import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { MovieService, Movie } from '../../../core/services/movie.service';
import { RatingService } from '../../../core/services/rating.service';

@Component({
  selector: 'app-details',
  standalone: true,
  imports: [CommonModule, FormsModule, TranslateModule],
  template: `
    <div class="container mx-auto px-4 py-8 animate-fade-in" *ngIf="movie">
      <div class="flex flex-col md:flex-row gap-8">
        <img [src]="movie.poster_url" class="w-full md:w-80 rounded-xl shadow-lg">
        <div class="flex-1">
          <h1 class="text-3xl font-bold mb-4">{{ movie.title }}</h1>
          <p class="text-gray-600 dark:text-gray-400 mb-4">{{ movie.overview }}</p>
          <div class="mb-4"><span class="text-yellow-500">⭐ {{ movieStats.average }}/5</span> ({{ movieStats.total_ratings }} avaliações)</div>
          <div class="flex gap-4 mb-6">
            <select [(ngModel)]="selectedRating" class="input w-32">
              <option *ngFor="let r of [1,2,3,4,5]" [value]="r">{{ r }} estrela{{ r > 1 ? 's' : '' }}</option>
            </select>
            <button (click)="saveRating()" class="btn-primary">{{ userRating ? 'Atualizar' : 'Avaliar' }}</button>
            <button *ngIf="userRating" (click)="deleteRating()" class="btn-secondary">Remover</button>
          </div>
          <div *ngIf="successMessage" class="text-green-600 text-sm mb-2">{{ successMessage }}</div>
          <div *ngIf="errorMessage" class="text-red-600 text-sm mb-2">{{ errorMessage }}</div>
        </div>
      </div>
    </div>
  `
})
export class DetailsComponent implements OnInit {
  movie: Movie | null = null;
  userRating: number | null = null;
  selectedRating = 5;
  movieStats = { average: 0, total_ratings: 0 };
  successMessage = '';
  errorMessage = '';

  constructor(
    private route: ActivatedRoute,
    private movieService: MovieService,
    private ratingService: RatingService
  ) {}

  ngOnInit() {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.movieService.getMovieDetails(+id).subscribe({
        next: (response) => { if (response.success) { this.movie = response.data; this.loadMovieStats(+id); } }
      });
      this.ratingService.getUserRating(+id).subscribe({
        next: (response) => { if (response.success && response.data) { this.userRating = response.data.rating; this.selectedRating = this.userRating; } }
      });
    }
  }

  loadMovieStats(movieId: number) {
    this.ratingService.getMovieStats(movieId).subscribe({
      next: (response) => { if (response.success) { this.movieStats = response.data; } }
    });
  }

  saveRating() {
    if (!this.movie) return;
    this.ratingService.saveRating(this.movie.id, this.selectedRating).subscribe({
      next: (response) => {
        if (response.success) {
          this.successMessage = 'Avaliação salva!';
          this.userRating = this.selectedRating;
          this.loadMovieStats(this.movie!.id);
          setTimeout(() => { this.successMessage = ''; }, 3000);
        }
      },
      error: () => { this.errorMessage = 'Erro ao salvar avaliação'; setTimeout(() => { this.errorMessage = ''; }, 3000); }
    });
  }

  deleteRating() {
    if (!this.movie) return;
    if (confirm('Remover esta avaliação?')) {
      this.ratingService.deleteRating(this.movie.id).subscribe({
        next: () => {
          this.userRating = null;
          this.selectedRating = 5;
          this.loadMovieStats(this.movie!.id);
          this.successMessage = 'Avaliação removida';
          setTimeout(() => { this.successMessage = ''; }, 3000);
        }
      });
    }
  }
}
