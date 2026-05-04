export class Classe {
  id: number;
  etablissement_id: number;
  annee_academique_id: number;
  nom: string;
  niveau: string;
  serie?: string;
  effectif: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;

  // Relations (optionnelles)
  etablissement?: any;
  anneeAcademique?: any;
  eleves?: any[];

  constructor(data: any = {}) {
    this.id = data.id || 0;
    this.etablissement_id = data.etablissement_id || 0;
    this.annee_academique_id = data.annee_academique_id || 0;
    this.nom = data.nom || '';
    this.niveau = data.niveau || '';
    this.serie = data.serie || '';
    this.effectif = data.effectif || 0;
    this.created_at = data.created_at || '';
    this.updated_at = data.updated_at || '';
    this.deleted_at = data.deleted_at || null;

    // Relations
    this.etablissement = data.etablissement || null;
    this.anneeAcademique = data.anneeAcademique || null;
    this.eleves = data.eleves || [];
  }

  /**
   * Obtenir le nom complet (ex: "6ème A - Série C")
   */
  get nomComplet(): string {
    return this.serie ? `${this.nom} - ${this.serie}` : this.nom;
  }

  /**
   * Vérifier si la classe a des élèves
   */
  hasEleves(): boolean {
    return this.effectif > 0;
  }

  /**
   * Obtenir le taux de remplissage (si capacité max définie)
   */
  getTauxRemplissage(capaciteMax: number = 50): number {
    if (capaciteMax === 0) return 0;
    return Math.round((this.effectif / capaciteMax) * 100);
  }
}
