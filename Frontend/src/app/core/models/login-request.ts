export class LoginRequest {
  email: string;
  mot_de_passe: string;

  constructor(email: string = '', password: string = '') {
    this.email = email;
    this.mot_de_passe = password;
  }
}
