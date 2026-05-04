export class Eleve {
  id: number;
  etablissement_id: number;
  classe_id: number;
  matricule: string;
  nom: string;
  prenom: string;
  date_naissance: string;
  lieu_naissance?: string;
  sexe: 'M' | 'F';
  contact_parent?: string;
  nom_parent?: string;
  archive: boolean;
  date_archivage?: string;
  created_at: string;
  updated_at: string;
  deleted_at?: string;

  // Relations (optionnelles)
  etablissement?: any;
  classe?: any;
  photos?: any[];
  photoActive?: any;
  cartes?: any[];
  carteActive?: any;

  constructor(data: any = {}) {
    this.id = data.id || 0;
    this.etablissement_id = data.etablissement_id || 0;
    this.classe_id = data.classe_id || 0;
    this.matricule = data.matricule || '';
    this.nom = data.nom || '';
    this.prenom = data.prenom || '';
    this.date_naissance = data.date_naissance || '';
    this.lieu_naissance = data.lieu_naissance || '';
    this.sexe = data.sexe || 'M';
    this.contact_parent = data.contact_parent || '';
    this.nom_parent = data.nom_parent || '';
    this.archive = data.archive || false;
    this.date_archivage = data.date_archivage || null;
    this.created_at = data.created_at || '';
    this.updated_at = data.updated_at || '';
    this.deleted_at = data.deleted_at || null;

    // Relations
    this.etablissement = data.etablissement || null;
    this.classe = data.classe || null;
    this.photos = data.photos || [];
    this.photoActive = data.photoActive || null;
    this.cartes = data.cartes || [];
    this.carteActive = data.carteActive || null;
  }

  /**
   * Obtenir le nom complet
   */
  get nomComplet(): string {
    return `${this.prenom} ${this.nom}`;
  }

  /**
   * Obtenir l'âge
   */
  get age(): number {
    if (!this.date_naissance) return 0;
    const today = new Date();
    const birthDate = new Date(this.date_naissance);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }
    return age;
  }

  /**
   * Vérifier si l'élève a une photo
   */
  hasPhoto(): boolean {
    return !!this.photoActive;
  }

  /**
   * Vérifier si l'élève a une carte
   */
  hasCarte(): boolean {
    return !!this.carteActive;
  }

  /**
   * Obtenir le sexe en texte
   */
  get sexeLibelle(): string {
    return this.sexe === 'M' ? 'Masculin' : 'Féminin';
  }

  /**
   * Vérifier si l'élève est archivé
   */
  isArchived(): boolean {
    return this.archive === true;
  }

  /**
   * Obtenir la date de naissance formatée
   */
  getDateNaissanceFormatted(): string {
    if (!this.date_naissance) return '-';
    const date = new Date(this.date_naissance);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  }
}
