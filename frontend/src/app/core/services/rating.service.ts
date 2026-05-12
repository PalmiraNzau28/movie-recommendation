import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface Rating {
  id: number;
  user_id: number;
  movie_id: number;
  rating: number;
  created_at: string;
  title: string;
  poster_path: string;
  tmdb_id: number;
  genre_ids: number[];
}

@Injectable({ providedIn: 'root' })
export class RatingService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  saveRating(movieId: number, rating: number) {
    return this.http.post<{ success: boolean; data: any }>(
      `${this.apiUrl}/ratings.php`,
      { movie_id: movieId, rating },
      { withCredentials: true }
    );
  }

  getUserRating(movieId: number) {
    return this.http.get<{ success: boolean; data: { rating: number } | null }>(
      `${this.apiUrl}/ratings.php?action=user-rating&movie_id=${movieId}`,
      { withCredentials: true }
    );
  }

  getUserRatings(page: number = 1, perPage: number = 20) {
    return this.http.get<{ success: boolean; data: Rating[] }>(
      `${this.apiUrl}/ratings.php?action=my-ratings&page=${page}&per_page=${perPage}`,
      { withCredentials: true }
    );
  }

  deleteRating(movieId: number) {
    return this.http.delete<{ success: boolean }>(
      `${this.apiUrl}/ratings.php`,
      { body: { movie_id: movieId }, withCredentials: true }
    );
  }

  getMovieStats(movieId: number) {
    return this.http.get<{ success: boolean; data: { average: number; total_ratings: number } }>(
      `${this.apiUrl}/ratings.php?action=stats&movie_id=${movieId}`,
      { withCredentials: true }
    );
  }
}
