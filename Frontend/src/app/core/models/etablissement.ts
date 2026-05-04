export class Etablissement {
  id?: number;
  nom: string;
  adresse: string;
  telephone?: string;
  email?: string;
  logo?: string;
  logo_url?: string;
  ville: string;
  proviseur_id?: number;
  created_at?: Date;
  updated_at?: Date;

  constructor(data: Partial<Etablissement> = {}) {
    this.id = data.id;
    this.nom = data.nom || '';
    this.adresse = data.adresse || '';
    this.telephone = data.telephone;
    this.email = data.email;
    this.logo = data.logo;
    this.logo_url = data.logo_url;
    this.ville = data.ville || '';
    this.proviseur_id = data.proviseur_id;
    this.created_at = data.created_at;
    this.updated_at = data.updated_at;
  }
}
