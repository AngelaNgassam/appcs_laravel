import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CarteService } from '../../../core/service/carte.service';
import { ModeleCarteService } from '../../../core/service/modele-carte.service';
import { ClasseService } from '../../../core/service/classe.service';

@Component({
  selector: 'app-proviseur-cartes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './cartes.component.html',
  styleUrls: ['./cartes.component.scss']
})
export class ProviseurCartesComponent implements OnInit {
  loading = true;
  error: string | null = null;

  generationResult: any | null = null;

  // Data
  modeles: any[] = [];
  classes: any[] = [];
  cartes: any[] = [];

  // Filters / actions
  selectedModeleId: number | null = null;
  selectedClasseId: number | null = null;
  statut: 'en_attente' | 'generee' | 'imprimee' | 'distribuee' | '' = 'generee';

  // Pagination
  currentPage = 1;
  totalPages = 1;
  total = 0;

  // Action
  isGenerating = false;
  downloadingId: number | null = null;

  // ✨ NOUVEAU : Sélection multiple pour planches
  selectedCartes: number[] = [];
  isGeneratingPlanche = false;

  // ✨ NOUVEAU : Statistiques
  statistiques: any = null;
  showStats = false;

  // ✨ NOUVEAU : Vue des modèles
  showModelesGrid = false;

  constructor(
    private carteService: CarteService,
    private modeleCarteService: ModeleCarteService,
    private classeService: ClasseService
  ) {}

  ngOnInit(): void {
    this.loadInitial();
    this.loadStatistiques();
  }

  /**
   * Watcher pour le changement de classe
   */
  onClasseChange(): void {
    this.currentPage = 1;

    // Si une classe est sélectionnée, charger les élèves de cette classe
    if (this.selectedClasseId) {
      this.loadElevesClasse();
    } else {
      this.loadCartes();
    }
  }

  /**
   * Charger les élèves d'une classe avec leurs cartes
   */
  private loadElevesClasse(): void {
    this.loading = true;
    this.error = null;

    this.carteService.getElevesClasse(this.selectedClasseId!).subscribe({
      next: (res: any) => {  // ✅ FIX TS2339: Typage explicite 'any' pour accès flexible à 'total'
        if (res.success) {
          // Transformer les élèves en format "cartes" pour affichage
          this.cartes = (res.data || []).map((eleve: any) => ({
            id: eleve.carte?.id || null,
            eleve_id: eleve.id,
            eleve: {
              id: eleve.id,
              matricule: eleve.matricule,
              nom: eleve.nom,
              prenom: eleve.prenom,
              nom_complet: eleve.nom_complet,
              classe_id: eleve.classe_id,
            },
            classe: eleve.classe,
            statut: eleve.carte?.statut || 'en_attente',
            date_generation: eleve.carte?.date_generation || null,
            modele: eleve.carte?.modele || null,
            has_photo: eleve.has_photo,
            has_carte: eleve.has_carte,
          }));
          this.total = (res as any).total || 0;  // ✅ FIX TS2339: Cast sûr + fallback
        } else {
          this.error = res.message || 'Erreur chargement élèves';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement élèves classe', err);
        this.error = 'Impossible de charger les élèves';
        this.loading = false;
      }
    });
  }

  loadInitial(): void {
    this.loading = true;
    this.error = null;

    // Charger en séquence simple (services différents)
    this.modeleCarteService.listModeles({ per_page: 100, actif: true }).subscribe({
      next: (res) => {
        const raw: any = (res as any)?.data;
        const list = Array.isArray(raw) ? raw : (Array.isArray(raw?.data) ? raw.data : []);
        this.modeles = res.success ? list : [];
        this.loadClasses();
      },
      error: (err) => {
        console.error('Erreur chargement modeles', err);
        this.modeles = [];
        this.loadClasses();
      }
    });
  }

  private loadClasses(): void {
    this.classeService.getClasses({ per_page: 100 }).subscribe({
      next: (res) => {
        this.classes = res.success ? (res.data || []) : [];
        this.loadCartes();
      },
      error: (err) => {
        console.error('Erreur chargement classes', err);
        this.classes = [];
        this.loadCartes();
      }
    });
  }

  loadCartes(): void {
    this.loading = true;
    this.error = null;

    this.carteService.listCartes({
      page: this.currentPage,
      per_page: 20,
      statut: (this.statut || undefined) as any,
      classe_id: this.selectedClasseId || undefined
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.cartes = res.data || [];
          if (res.pagination) {
            this.totalPages = res.pagination.last_page;
            this.total = res.pagination.total || 0;  // ✅ Sécurité supplémentaire
          }
        } else {
          this.error = res.message || 'Erreur chargement cartes';
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement cartes', err);
        this.error = 'Impossible de charger les cartes';
        this.loading = false;
      }
    });
  }

  // ✨ NOUVEAU : Charger les statistiques
  loadStatistiques(): void {
    this.carteService.getStatistiques().subscribe({
      next: (res) => {
        if (res.success) {
          this.statistiques = res.data;
        }
      },
      error: (err) => {
        console.error('Erreur chargement statistiques', err);
      }
    });
  }

  onFilter(): void {
    this.currentPage = 1;
    this.loadCartes();
  }

  goToPage(page: number): void {
    this.currentPage = page;
    this.loadCartes();
  }

  genererClasse(): void {
    if (!this.selectedClasseId) {
      alert('Veuillez choisir une classe');
      return;
    }

    if (!confirm('Voulez-vous générer toutes les cartes de cette classe ?')) {
      return;
    }

    this.isGenerating = true;
    this.generationResult = null;

    const modeleId = this.selectedModeleId || undefined;

    this.carteService.genererCartesClasse(this.selectedClasseId, modeleId).subscribe({
      next: (res) => {
        this.isGenerating = false;
        if (res.success) {
          this.generationResult = res.data || null;
          alert(res.message || 'Génération terminée');
          this.loadCartes();
          this.loadStatistiques();
        } else {
          alert(res.message || 'Erreur génération');
        }
      },
      error: (err) => {
        console.error('Erreur génération cartes classe', err);
        this.isGenerating = false;
        this.generationResult = null;
        alert('Erreur génération');
      }
    });
  }

  // ✨ NOUVEAU : Générer une planche d'impression
  genererPlanche(): void {
    if (this.selectedCartes.length === 0) {
      alert('Veuillez sélectionner au moins une carte');
      return;
    }

    if (!confirm(`Générer une planche avec ${this.selectedCartes.length} carte(s) ?`)) {
      return;
    }

    this.isGeneratingPlanche = true;

    // ✅ FIX TS2345: Conversion null → undefined
    this.carteService.genererPlanche(this.selectedCartes, this.selectedModeleId ?? undefined).subscribe({
      next: (res) => {
        this.isGeneratingPlanche = false;
        if (res.success && res.data) {
          alert(`Planche générée avec ${res.data.nombre_cartes} carte(s) !`);

          // Télécharger automatiquement la planche
          if (res.data.url) {
            window.open(res.data.url, '_blank');
          }

          this.selectedCartes = [];
        } else {
          alert(res.message || 'Erreur génération planche');
        }
      },
      error: (err) => {
        console.error('Erreur génération planche', err);
        this.isGeneratingPlanche = false;
        alert('Erreur lors de la génération de la planche');
      }
    });
  }

  // ✨ NOUVEAU : Sélectionner/Désélectionner une carte
  toggleSelection(eleveId: number): void {
    const index = this.selectedCartes.indexOf(eleveId);
    if (index > -1) {
      this.selectedCartes.splice(index, 1);
    } else {
      this.selectedCartes.push(eleveId);
    }
  }

  // ✨ NOUVEAU : Sélectionner toutes les cartes
  selectAll(): void {
    if (this.selectedCartes.length === this.cartes.length) {
      this.selectedCartes = [];
    } else {
      this.selectedCartes = this.cartes
        .filter(c => c.eleve_id)
        .map(c => c.eleve_id);
    }
  }

  // ✨ NOUVEAU : Vérifier si une carte est sélectionnée
  isSelected(eleveId: number): boolean {
    return this.selectedCartes.includes(eleveId);
  }

  telecharger(carte: any): void {
    if (!carte?.id) return;

    this.downloadingId = carte.id;
    this.carteService.telechargerCarte(carte.id).subscribe({
      next: (blob) => {
        this.downloadingId = null;
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const matricule = carte?.eleve?.matricule || 'carte';
        a.download = `carte_${matricule}.pdf`;
        a.click();
        window.URL.revokeObjectURL(url);
      },
      error: (err) => {
        console.error('Erreur téléchargement', err);
        this.downloadingId = null;
        alert('Erreur téléchargement');
      }
    });
  }

  // ✨ NOUVEAU : Prévisualiser une carte
  previsualiser(carte: any): void {
    if (!carte?.id) return;
    this.carteService.previsualiserCarte(carte.id);
  }

  // ✨ NOUVEAU : Marquer comme imprimée
  marquerCommeImprimee(carte: any): void {
    if (!carte?.id) return;

    if (!confirm('Marquer cette carte comme imprimée ?')) {
      return;
    }

    this.carteService.marquerCommeImprimee(carte.id).subscribe({
      next: (res) => {
        if (res.success) {
          alert('Carte marquée comme imprimée');
          this.loadCartes();
          this.loadStatistiques();
        } else {
          alert(res.message || 'Erreur');
        }
      },
      error: (err) => {
        console.error('Erreur marquage impression', err);
        alert('Erreur lors du marquage');
      }
    });
  }

  // ✨ NOUVEAU : Obtenir le badge de statut
  getStatutBadgeClass(statut: string): string {
    switch (statut) {
      case 'en_attente': return 'bg-warning';
      case 'generee': return 'bg-info';
      case 'imprimee': return 'bg-primary';
      case 'distribuee': return 'bg-success';
      default: return 'bg-secondary';
    }
  }

  // ✨ NOUVEAU : Formater le statut
  formatStatut(statut: string): string {
    switch (statut) {
      case 'en_attente': return 'En attente';
      case 'generee': return 'Générée';
      case 'imprimee': return 'Imprimée';
      case 'distribuee': return 'Distribuée';
      default: return statut;
    }
  }

  // ✨ NOUVEAU : Toggle affichage statistiques
  toggleStats(): void {
    this.showStats = !this.showStats;
  }

  // Helper pour Math.min dans le template
  getMin(a: number, b: number): number {
    return Math.min(a, b);
  }

  // ✨ NOUVEAU : Toggle vue des modèles
  toggleModelesView(): void {
    this.showModelesGrid = !this.showModelesGrid;
  }

  // ✨ NOUVEAU : Obtenir le nom du modèle sélectionné
  getSelectedModeleName(): string {
    if (this.selectedModeleId === null) {
      return 'Template Cameroun (Par défaut)';
    }
    const modele = this.modeles.find(m => m.id === this.selectedModeleId);
    return modele ? modele.nom_modele : 'Inconnu';
  }
}
