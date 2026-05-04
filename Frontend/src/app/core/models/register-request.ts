export class RegisterRequest {
  // Infos proviseur
  nom: string;
  prenom: string;
  email: string;
  mot_de_passe: string;
  telephone?: string;

  // Infos établissement
  nom_etablissement: string;
  adresse_etablissement: string;
  ville_etablissement: string;
  telephone_etablissement?: string;
  email_etablissement?: string;
  logo?: File | null; // ✅ Ajout du logo

  constructor(data: Partial<RegisterRequest> = {}) {
    this.nom = data.nom || '';
    this.prenom = data.prenom || '';
    this.email = data.email || '';
    this.mot_de_passe = data.mot_de_passe || '';
    this.telephone = data.telephone;
    this.nom_etablissement = data.nom_etablissement || '';
    this.adresse_etablissement = data.adresse_etablissement || '';
    this.ville_etablissement = data.ville_etablissement || '';
    this.telephone_etablissement = data.telephone_etablissement;
    this.email_etablissement = data.email_etablissement;
    this.logo = data.logo || null; // ✅ Ajout
  }
}
