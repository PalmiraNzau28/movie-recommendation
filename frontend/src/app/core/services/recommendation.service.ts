import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Movie } from './movie.service';

interface RecommendationResponse {
  success: boolean;
  data: {
    recommendations: Movie[];
    total: number;
    user_id: number;
  };
}

interface ExplanationResponse {
  success: boolean;
  data: {
    has_enough_data: boolean;
    total_liked_movies: number;
    genre_preferences: Array<{
      genre_id: number;
      genre_name: string;
      weight: number;
      confidence: string;
    }>;
    algorithm: string;
    explanation: string;
  };
}

@Injectable({ providedIn: 'root' })
export class RecommendationService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  getRecommendations(limit: number = 20) {
    return this.http.get<RecommendationResponse>(
      `${this.apiUrl}/recommendations.php?action=get&limit=${limit}`,
      { withCredentials: true }
    );
  }

  getExplanation() {
    return this.http.get<ExplanationResponse>(
      `${this.apiUrl}/recommendations.php?action=explain`,
      { withCredentials: true }
    );
  }
}
