import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { RecommendationService } from '../../core/services/recommendation.service';
import { Movie } from '../../core/services/movie.service';

@Component({
  selector: 'app-recommendations',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule],
  template: `
    <div class="container mx-auto px-4 py-8 animate-fade-in">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">{{ 'AI_RECOMMENDATIONS' | translate }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">{{ explanation }}</p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <div *ngFor="let movie of recommendations" class="card hover:scale-105 transition-transform duration-300 cursor-pointer" [routerLink]="['/movies', movie.id]">
          <img [src]="movie.poster_url" class="w-full h-64 object-cover">
          <div class="p-4">
            <h3 class="font-semibold text-lg">{{ movie.title }}</h3>
            <div class="mt-2">
              <span class="text-sm text-purple-600">🎯 {{ movie.similarity_score }}% match</span>
              <div class="text-xs text-gray-500 mt-1">⭐ {{ movie.movie_average_rating }}/5 ({{ movie.total_ratings }} avaliações)</div>
              <div class="flex flex-wrap gap-1 mt-2"><span *ngFor="let genre of movie.why_recommended?.slice(0,3)" class="text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full">{{ genre.name }}</span></div>
            </div>
          </div>
        </div>
      </div>
      <div *ngIf="recommendations.length === 0 && !loading" class="text-center py-12">
        <p class="text-gray-500">{{ 'NO_RECOMMENDATIONS' | translate }}</p>
        <p class="text-sm text-gray-400 mt-2">{{ 'RATE_MORE_MOVIES' | translate }}</p>
      </div>
    </div>
  `
})
export class RecommendationsComponent implements OnInit {
  recommendations: Movie[] = [];
  loading = true;
  explanation = 'Carregando...';
  constructor(private recommendationService: RecommendationService) {}
  ngOnInit() {
    this.recommendationService.getExplanation().subscribe({
      next: (response) => {
        if (response.success && response.data.genre_preferences.length > 0) {
          this.explanation = `Baseado nos seus gêneros favoritos: ${response.data.genre_preferences.slice(0,3).map(g => g.genre_name).join(', ')}`;
        } else {
          this.explanation = response.data.explanation || 'Avalie mais filmes para receber recomendações personalizadas';
        }
      }
    });
    this.recommendationService.getRecommendations().subscribe({
      next: (response) => { if (response.success) { this.recommendations = response.data.recommendations; } this.loading = false; },
      error: () => { this.loading = false; }
    });
  }
}
