import { User } from './user';
import { Etablissement } from './etablissement';

export class AuthResponse {
  success: boolean;
  token: string;
  user: User;
  etablissement?: Etablissement; // ✅ Ajouter cette ligne
  message?: string;

  constructor(data: Partial<AuthResponse> = {}) {
    this.success = data.success || false;
    this.token = data.token || '';
    this.user = data.user ? new User(data.user) : new User();
    this.etablissement = data.etablissement ? new Etablissement(data.etablissement) : undefined;
    this.message = data.message;
  }
}
