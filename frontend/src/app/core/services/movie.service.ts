import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

export interface Movie {
  id: number;
  tmdb_id: number;
  title: string;
  overview: string;
  poster_path: string;
  poster_url: string;
  genre_ids: number[];
  popularity: number;
  movie_average_rating?: number;
  total_ratings?: number;
  similarity_score?: number;
  why_recommended?: Array<{ id: number; name: string; weight: number }>;
}

@Injectable({ providedIn: 'root' })
export class MovieService {
  private apiUrl = 'http://localhost:8000/api';

  constructor(private http: HttpClient) {}

  getPopularMovies(limit: number = 20, refresh: boolean = false) {
    // refresh=true sempre busca da API, false pode usar cache
    const queryParams = [`limit=${limit}`];
    if (refresh) {
      queryParams.push('refresh=true');
    }
    const params = queryParams.length ? `&${queryParams.join('&')}` : '';
    return this.http.get<{ success: boolean; data: Movie[] }>(
      `${this.apiUrl}/movies.php?action=popular${params}`
    );
  }

  searchMovies(query: string) {
    return this.http.get<{ success: boolean; data: Movie[] }>(
      `${this.apiUrl}/movies.php?action=search&q=${encodeURIComponent(query)}`
    );
  }

  getMovieDetails(id: number) {
    return this.http.get<{ success: boolean; data: Movie }>(
      `${this.apiUrl}/movies.php?action=details&id=${id}`
    );
  }
}