import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClasseService } from '../../../core/service/classe.service';
import { EleveService } from '../../../core/service/eleve.service';
import { Classe } from '../../../core/models/classe';
import { Eleve } from '../../../core/models/eleve';
import { EleveModalComponent } from '../../../shared/components/eleve-modal/eleve-modal.component';
import { ConfirmModalComponent } from '../../../shared/components/confirm-modal/confirm-modal.component';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { Document, Packer, Paragraph, Table, TableCell, TableRow, WidthType, BorderStyle, AlignmentType, HeadingLevel, TextRun } from 'docx';
import { saveAs } from 'file-saver';

@Component({
  selector: 'app-classe-details',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule, EleveModalComponent, ConfirmModalComponent],
  templateUrl: './classe-details.component.html',
  styleUrls: ['./classe-details.component.scss']
})
export class ClasseDetailsComponent implements OnInit {

  classeId: number = 0;
  classe: Classe | null = null;
  eleves: Eleve[] = [];
  statistiques: any = null;

  loading = true;
  error: string | null = null;

  // Filtres
  searchTerm = '';
  showArchived = false;

  // Pagination
  currentPage = 1;
  totalPages = 1;
  totalEleves = 0;

  // Modal de détails élève
  selectedEleve: Eleve | null = null;
  showEleveModal = false;

  // Modal de confirmation d'archivage
  showArchiveConfirm = false;
  eleveToArchive: Eleve | null = null;

  // Modal de confirmation de désarchivage
  showUnarchiveConfirm = false;
  eleveToUnarchive: Eleve | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private classeService: ClasseService,
    private eleveService: EleveService
  ) {}

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.classeId = +params['id'];
      this.loadClasseDetails();
    });
  }

  /**
   * Charger les détails de la classe
   */
  loadClasseDetails(): void {
    this.loading = true;
    this.error = null;

    this.classeService.getClasse(this.classeId).subscribe({
      next: (response) => {
        if (response.success) {
          this.classe = new Classe(response.data);
          this.statistiques = response.statistiques;

          // Charger tous les élèves (actifs + archivés)
          const tousLesEleves = [
            ...(response.data.elevesActifs || []),
            ...(response.data.elevesArchives || [])
          ];
          this.eleves = tousLesEleves.map((e: any) => new Eleve(e));
          this.totalEleves = this.eleves.length;

          console.log('Élèves chargés:', this.eleves);
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement classe', err);
        this.error = 'Impossible de charger les détails de la classe';
        this.loading = false;
      }
    });
  }

  /**
   * Filtrer les élèves
   */
  get elevesFiltered(): Eleve[] {
    let filtered = this.eleves;

    // Filtrer les archivés
    if (!this.showArchived) {
      filtered = filtered.filter(e => !e.isArchived());
    } else {
      filtered = filtered.filter(e => e.isArchived());
    }

    // Recherche
    if (this.searchTerm) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(e =>
        e.nom.toLowerCase().includes(term) ||
        e.prenom.toLowerCase().includes(term) ||
        e.matricule.toLowerCase().includes(term)
      );
    }

    return filtered;
  }

  /**
   * Afficher le modal de détails d'un élève
   */
  showEleveDetails(eleve: Eleve): void {
    this.selectedEleve = eleve;
    this.showEleveModal = true;
  }

  /**
   * Fermer le modal de détails
   */
  closeEleveModal(): void {
    this.showEleveModal = false;
    this.selectedEleve = null;
  }

  /**
   * Demander confirmation pour archiver
   */
  confirmArchiveEleve(eleve: Eleve): void {
    this.eleveToArchive = eleve;
    this.showArchiveConfirm = true;
  }

  /**
   * Archiver un élève
   */
  archiveEleve(): void {
    if (!this.eleveToArchive) return;

    this.eleveService.archiver(this.eleveToArchive.id).subscribe({
      next: (response) => {
        if (response.success) {
          // Mettre à jour l'élève dans la liste
          const index = this.eleves.findIndex(e => e.id === this.eleveToArchive!.id);
          if (index !== -1) {
            this.eleves[index].archive = true;
            this.eleves[index].date_archivage = new Date().toISOString();
          }

          // Fermer la modal
          this.showArchiveConfirm = false;
          this.eleveToArchive = null;

          // Recharger les statistiques
          this.loadClasseDetails();
        }
      },
      error: (err) => {
        console.error('Erreur archivage', err);
        alert('Erreur lors de l\'archivage de l\'élève');
        this.showArchiveConfirm = false;
        this.eleveToArchive = null;
      }
    });
  }

  /**
   * Annuler l'archivage
   */
  cancelArchive(): void {
    this.showArchiveConfirm = false;
    this.eleveToArchive = null;
  }

  /**
   * Demander confirmation pour désarchiver
   */
  confirmUnarchiveEleve(eleve: Eleve): void {
    this.eleveToUnarchive = eleve;
    this.showUnarchiveConfirm = true;
  }

  /**
   * Désarchiver un élève
   */
  unarchiveEleve(): void {
    if (!this.eleveToUnarchive) return;

    this.eleveService.desarchiver(this.eleveToUnarchive.id).subscribe({
      next: (response) => {
        if (response.success) {
          // Mettre à jour l'élève dans la liste
          const index = this.eleves.findIndex(e => e.id === this.eleveToUnarchive!.id);
          if (index !== -1) {
            this.eleves[index].archive = false;
            this.eleves[index].date_archivage = undefined; // ✅ CORRIGÉ
          }

          // Fermer la modal
          this.showUnarchiveConfirm = false;
          this.eleveToUnarchive = null;

          // Recharger les statistiques
          this.loadClasseDetails();
        }
      },
      error: (err) => {
        console.error('Erreur désarchivage', err);
        alert('Erreur lors du désarchivage de l\'élève');
        this.showUnarchiveConfirm = false;
        this.eleveToUnarchive = null;
      }
    });
  }

  /**
   * Annuler le désarchivage
   */
  cancelUnarchive(): void {
    this.showUnarchiveConfirm = false;
    this.eleveToUnarchive = null;
  }

  /**
   * Exporter en PDF
   */
  exportPDF(): void {
    const doc = new jsPDF();
    const elevesToExport = this.elevesFiltered;

    // Titre
    doc.setFontSize(18);
    doc.setFont('helvetica', 'bold');
    doc.text(`Liste des élèves - ${this.classe?.nom || ''}`, 14, 20);

    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text(`${this.showArchived ? 'Élèves archivés' : 'Élèves actifs'} (${elevesToExport.length})`, 14, 30);
    doc.text(`Date: ${new Date().toLocaleDateString('fr-FR')}`, 14, 37);

    // Préparer les données pour le tableau
    const tableData = elevesToExport.map((eleve, index) => [
      (index + 1).toString(),
      eleve.matricule,
      eleve.nom,
      eleve.prenom,
      eleve.sexeLibelle,
      eleve.getDateNaissanceFormatted(),
      eleve.age.toString() + ' ans',
      eleve.hasPhoto() ? 'Oui' : 'Non',
      eleve.hasCarte() ? 'Oui' : 'Non'
    ]);

    // Générer le tableau
    autoTable(doc, {
      head: [['N°', 'Matricule', 'Nom', 'Prénom', 'Sexe', 'Date naiss.', 'Âge', 'Photo', 'Carte']],
      body: tableData,
      startY: 45,
      styles: {
        fontSize: 9,
        cellPadding: 3
      },
      headStyles: {
        fillColor: [16, 185, 129],
        textColor: 255,
        fontStyle: 'bold'
      },
      alternateRowStyles: {
        fillColor: [249, 250, 251]
      }
    });

    // Sauvegarder
    const fileName = `eleves_${this.classe?.nom.replace(/\s+/g, '_')}_${this.showArchived ? 'archives' : 'actifs'}_${new Date().getTime()}.pdf`;
    doc.save(fileName);
  }

  /**
   * Exporter en Word - ✅ CORRIGÉ
   */
  async exportWord(): Promise<void> {
    const elevesToExport = this.elevesFiltered;

    // Créer les lignes du tableau
    const tableRows: TableRow[] = [
      // En-tête - Utilisation correcte de TextRun avec bold
      new TableRow({
        children: [
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'N°', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          }),
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'Matricule', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          }),
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'Nom', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          }),
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'Prénom', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          }),
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'Sexe', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          }),
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'Date naissance', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          }),
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'Âge', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          }),
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'Photo', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          }),
          new TableCell({
            children: [new Paragraph({
              children: [new TextRun({ text: 'Carte', bold: true })],
              alignment: AlignmentType.CENTER
            })],
            shading: { fill: '10b981' }
          })
        ]
      }),
      // Données
      ...elevesToExport.map((eleve, index) => new TableRow({
        children: [
          new TableCell({ children: [new Paragraph({ text: (index + 1).toString(), alignment: AlignmentType.CENTER })] }),
          new TableCell({ children: [new Paragraph(eleve.matricule)] }),
          new TableCell({ children: [new Paragraph(eleve.nom)] }),
          new TableCell({ children: [new Paragraph(eleve.prenom)] }),
          new TableCell({ children: [new Paragraph({ text: eleve.sexeLibelle, alignment: AlignmentType.CENTER })] }),
          new TableCell({ children: [new Paragraph(eleve.getDateNaissanceFormatted())] }),
          new TableCell({ children: [new Paragraph({ text: eleve.age.toString() + ' ans', alignment: AlignmentType.CENTER })] }),
          new TableCell({ children: [new Paragraph({ text: eleve.hasPhoto() ? 'Oui' : 'Non', alignment: AlignmentType.CENTER })] }),
          new TableCell({ children: [new Paragraph({ text: eleve.hasCarte() ? 'Oui' : 'Non', alignment: AlignmentType.CENTER })] })
        ]
      }))
    ];

    const table = new Table({
      width: { size: 100, type: WidthType.PERCENTAGE },
      rows: tableRows,
      borders: {
        top: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
        bottom: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
        left: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
        right: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
        insideHorizontal: { style: BorderStyle.SINGLE, size: 1, color: '000000' },
        insideVertical: { style: BorderStyle.SINGLE, size: 1, color: '000000' }
      }
    });

    const doc = new Document({
      sections: [{
        children: [
          new Paragraph({
            text: `Liste des élèves - ${this.classe?.nom || ''}`,
            heading: HeadingLevel.HEADING_1,
            alignment: AlignmentType.CENTER,
            spacing: { after: 200 }
          }),
          new Paragraph({
            children: [
              new TextRun({
                text: `${this.showArchived ? 'Élèves archivés' : 'Élèves actifs'} (${elevesToExport.length})`,
                bold: true
              })
            ],
            alignment: AlignmentType.CENTER,
            spacing: { after: 100 }
          }),
          new Paragraph({
            text: `Date: ${new Date().toLocaleDateString('fr-FR')}`,
            alignment: AlignmentType.CENTER,
            spacing: { after: 300 }
          }),
          table
        ]
      }]
    });

    // Générer et télécharger
    const blob = await Packer.toBlob(doc);
    const fileName = `eleves_${this.classe?.nom.replace(/\s+/g, '_')}_${this.showArchived ? 'archives' : 'actifs'}_${new Date().getTime()}.docx`;
    saveAs(blob, fileName);
  }

  /**
   * Importer des élèves
   */
  importEleves(): void {
    this.router.navigate(['/proviseur/classes', this.classeId, 'importer']);
  }

  /**
   * Ajouter un élève manuellement
   */
  addEleve(): void {
    this.router.navigate(['/proviseur/eleves/nouveau'], {
      queryParams: { classe_id: this.classeId }
    });
  }

  /**
   * Voir un élève
   */
  viewEleve(eleveId: number): void {
    this.router.navigate(['/proviseur/eleves', eleveId]);
  }

  /**
   * Retour à la liste des classes
   */
  goBack(): void {
    this.router.navigate(['/proviseur/classes']);
  }

  /**
   * Obtenir la couleur du sexe
   */
  getSexeColor(sexe: string): string {
    return sexe === 'M' ? 'primary' : 'danger';
  }

  /**
   * Obtenir l'icône du sexe
   */
  getSexeIcon(sexe: string): string {
    return sexe === 'M' ? 'bi-gender-male' : 'bi-gender-female';
  }
}
