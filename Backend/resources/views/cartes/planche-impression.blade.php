<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Planche d'impression - Cartes Scolaires</title>
    <style>
        @page { 
            margin: 10mm;
            size: A4 portrait;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }
        
        .planche-container {
            width: 100%;
        }
        
        .planche-header {
            text-align: center;
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .planche-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 2mm;
        }
        
        .planche-info {
            font-size: 9pt;
            color: #6b7280;
        }
        
        .cartes-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5mm;
            width: 100%;
        }
        
        .carte-item {
            width: 85.6mm;
            height: 53.98mm;
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        /* Styles pour les cartes */
        .card {
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        /* Cameroun Model */
        .card.cameroun {
            border: 3px solid #007a3d;
        }
        
        .card.cameroun .header-cameroun {
            background: linear-gradient(to right, #007a3d 0%, #007a3d 33%, #ce1126 33%, #ce1126 66%, #fcd116 66%, #fcd116 100%);
            height: 10mm;
            position: relative;
            border-bottom: 2px solid #007a3d;
        }
        
        .card.cameroun .header-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5mm 4mm;
            border-radius: 2mm;
            text-align: center;
            font-size: 6pt;
        }
        
        .etoile {
            color: #fcd116;
            font-size: 10pt;
            font-weight: bold;
        }
        
        .republique {
            font-size: 7pt;
            font-weight: bold;
            color: #007a3d;
            text-transform: uppercase;
        }
        
        .devise {
            font-size: 5pt;
            color: #ce1126;
            font-style: italic;
            margin-top: 0.5mm;
        }
        
        .card-body {
            flex: 1;
            display: flex;
            padding: 3mm;
            gap: 3mm;
            position: relative;
        }
        
        .photo-section {
            flex-shrink: 0;
        }
        
        .photo-frame {
            width: 22mm;
            height: 28mm;
            border: 2px solid #007a3d;
            border-radius: 2mm;
            overflow: hidden;
            background: #f9fafb;
        }
        
        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            font-size: 6pt;
        }
        
        .card-title {
            font-size: 7pt;
            font-weight: bold;
            color: #ce1126;
            text-align: center;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }
        
        .info-row {
            display: flex;
            gap: 1mm;
            line-height: 1.1;
        }
        
        .info-label {
            font-weight: bold;
            color: #007a3d;
            min-width: 16mm;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #1f2937;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .matricule-box {
            background: #fcd116;
            padding: 0.5mm 1.5mm;
            border-radius: 2mm;
            font-weight: bold;
            color: #000;
            border: 1px solid #007a3d;
            font-size: 6pt;
            display: inline-block;
        }
        
        .classe-badge {
            background: #007a3d;
            color: white;
            padding: 0.5mm 1.5mm;
            border-radius: 2mm;
            font-weight: bold;
            font-size: 6pt;
            display: inline-block;
        }
        
        .qr-section {
            position: absolute;
            bottom: 8mm;
            right: 3mm;
            width: 18mm;
            height: 18mm;
            background: white;
            border: 2px solid #007a3d;
            border-radius: 2mm;
            padding: 1mm;
        }
        
        .qr-code {
            width: 100%;
            height: 100%;
        }
        
        .qr-code svg {
            width: 100% !important;
            height: 100% !important;
        }
        
        .card-footer {
            background: #007a3d;
            padding: 1.5mm 3mm;
            display: flex;
            justify-content: space-between;
            font-size: 5pt;
            color: white;
            border-top: 2px solid #007a3d;
            flex-shrink: 0;
        }
        
        .footer-item {
            flex: 1;
        }
        
        .footer-center {
            text-align: center;
            font-weight: bold;
            font-size: 6pt;
        }
        
        .footer-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="planche-container">
        <div class="planche-header">
            <div class="planche-title">📋 PLANCHE D'IMPRESSION - CARTES SCOLAIRES</div>
            <div class="planche-info">
                Généré le {{ now()->format('d/m/Y à H:i') }} | 
                Total: {{ count($cartes) }} carte(s) | 
                Modèle: {{ $modele?->nom_modele ?? 'Cameroun (Par défaut)' }}
            </div>
        </div>
        
        <div class="cartes-grid">
            @foreach($cartes as $carte)
                <div class="carte-item">
                    <div class="card cameroun">
                        <!-- En-tête avec drapeau Cameroun -->
                        <div class="header-cameroun">
                            <div class="header-content">
                                <div class="etoile">★</div>
                                <div class="republique">République du Cameroun</div>
                                <div class="devise">Paix - Travail - Patrie</div>
                            </div>
                        </div>
                        
                        <!-- Corps -->
                        <div class="card-body">
                            <!-- Photo -->
                            <div class="photo-section">
                                <div class="photo-frame">
                                    @if($carte['photoBase64'])
                                        <img src="{{ $carte['photoBase64'] }}" alt="Photo">
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Informations -->
                            <div class="info-section">
                                <div class="card-title">{{ $carte['etablissement']->nom }}</div>
                                
                                <div class="info-row">
                                    <div class="info-label">Matricule :</div>
                                    <div class="info-value">
                                        <span class="matricule-box">{{ $carte['eleve']->matricule }}</span>
                                    </div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Nom :</div>
                                    <div class="info-value">{{ strtoupper($carte['eleve']->nom) }}</div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Prénom(s) :</div>
                                    <div class="info-value">{{ ucwords(strtolower($carte['eleve']->prenom)) }}</div>
                                </div>
                                
                                <div class="info-row">
                                    <div class="info-label">Classe :</div>
                                    <div class="info-value">
                                        <span class="classe-badge">{{ $carte['classe']->nom }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- QR Code -->
                            <div class="qr-section">
                                <div class="qr-code">{!! $carte['qrCode'] !!}</div>
                            </div>
                        </div>
                        
                        <!-- Pied de page -->
                        <div class="card-footer">
                            <div class="footer-item">Délivré: {{ now()->format('d/m/Y') }}</div>
                            <div class="footer-center">CARTE OFFICIELLE</div>
                            <div class="footer-item footer-right">Expire: {{ now()->addYears(2)->format('d/m/Y') }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
