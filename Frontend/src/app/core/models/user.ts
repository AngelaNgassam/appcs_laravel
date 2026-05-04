export class User {
  id?: number;
  nom: string;
  prenom: string;
  email: string;
  mot_de_passe?: string; // Ne jamais stocker en clair côté frontend
  role: 'admin' | 'proviseur' | 'surveillant' | 'operateur';
  telephone?: string;
  etablissement_id?: number;
  cree_par?: number;
  actif: boolean;
  created_at?: Date;
  updated_at?: Date;

  constructor(data: Partial<User> = {}) {
    this.id = data.id;
    this.nom = data.nom || '';
    this.prenom = data.prenom || '';
    this.email = data.email || '';
    this.role = data.role || 'proviseur';
    this.telephone = data.telephone;
    this.etablissement_id = data.etablissement_id;
    this.cree_par = data.cree_par;
    this.actif = data.actif !== undefined ? data.actif : true;
    this.created_at = data.created_at;
    this.updated_at = data.updated_at;
  }
}
