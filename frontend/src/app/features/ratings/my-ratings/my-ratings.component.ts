import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { RatingService, Rating } from '../../../core/services/rating.service';

@Component({
  selector: 'app-my-ratings',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule],
  template: `
    <div class="container mx-auto px-4 py-8 animate-fade-in">
      <h1 class="text-3xl font-bold mb-8">{{ 'MY_RATINGS' | translate }}</h1>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div *ngFor="let rating of ratings" class="card cursor-pointer" [routerLink]="['/movies', rating.movie_id]">
          <img [src]="'https://image.tmdb.org/t/p/w500' + rating.poster_path" class="w-full h-48 object-cover">
          <div class="p-4">
            <h3 class="font-semibold">{{ rating.title }}</h3>
            <div class="flex items-center justify-between mt-2">
              <span class="text-yellow-500">⭐ {{ rating.rating }}/5</span>
              <button (click)="deleteRating($event, rating.movie_id)" class="text-red-500 hover:text-red-700">🗑️</button>
            </div>
          </div>
        </div>
      </div>
      <div *ngIf="ratings.length === 0 && !loading" class="text-center py-12"><p class="text-gray-500">{{ 'NO_RATINGS_YET' | translate }}</p></div>
    </div>
  `
})
export class MyRatingsComponent implements OnInit {
  ratings: Rating[] = [];
  loading = true;
  constructor(private ratingService: RatingService) {}
  ngOnInit() {
    this.loadRatings();
  }
  loadRatings() {
    this.ratingService.getUserRatings().subscribe({
      next: (response) => { if (response.success) { this.ratings = response.data; } this.loading = false; },
      error: () => { this.loading = false; }
    });
  }
  deleteRating(event: Event, movieId: number) {
    event.stopPropagation();
    if (confirm('Remover esta avaliação?')) {
      this.ratingService.deleteRating(movieId).subscribe(() => { this.loadRatings(); });
    }
  }
}
