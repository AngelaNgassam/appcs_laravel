<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Carte Scolaire Recto-Verso - {{ $eleve->matricule }}</title>
    <style>
        @page { 
            margin: 15mm;
            size: A4 landscape;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }
        
        .print-container {
            width: 100%;
            display: table;
        }
        
        .card-side {
            display: table-cell;
            width: 85.6mm;
            height: 53.98mm;
            vertical-align: top;
        }
        
        .card-side:first-child {
            padding-right: 10mm;
        }
        
        .cut-line {
            border-left: 2px dashed #cbd5e0;
            height: 100%;
            position: absolute;
            left: 50%;
            top: 0;
        }
        
        .instructions {
            text-align: center;
            margin-top: 10mm;
            color: #6b7280;
            font-size: 9pt;
        }
        
        /* Styles du RECTO */
        .card-recto {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 2px solid #4a5568;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .card-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 6px 10px;
            border-bottom: 3px solid #f59e0b;
            display: table;
            width: 100%;
        }
        
        .logo-section {
            display: table-cell;
            width: 50px;
            vertical-align: middle;
        }
        
        .logo {
            max-height: 35px;
            max-width: 45px;
            display: block;
        }
        
        .header-text {
            display: table-cell;
            vertical-align: middle;
            padding-left: 8px;
        }
        
        .school-name {
            font-size: 10pt;
            font-weight: bold;
            color: #1f2937;
            text-transform: uppercase;
            line-height: 1.2;
            margin-bottom: 2px;
        }
        
        .card-title {
            font-size: 7pt;
            color: #6b7280;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .card-body {
            padding: 8px 10px;
            background: rgba(255, 255, 255, 0.98);
            height: calc(100% - 50px);
            display: table;
            width: 100%;
        }
        
        .photo-section {
            display: table-cell;
            width: 90px;
            vertical-align: top;
            padding-right: 8px;
        }
        
        .photo-frame {
            width: 85px;
            height: 110px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
            background: #f9fafb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-section {
            display: table-cell;
            vertical-align: top;
            padding-top: 2px;
        }
        
        .info-row {
            margin-bottom: 4px;
            display: table;
            width: 100%;
        }
        
        .info-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 7pt;
            display: table-cell;
            width: 55px;
            padding-right: 4px;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 8pt;
            font-weight: 500;
            display: table-cell;
        }
        
        .matricule-highlight {
            background: #fef3c7;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            color: #92400e;
            display: inline-block;
        }
        
        .qr-section {
            position: absolute;
            bottom: 8px;
            right: 10px;
            width: 50px;
            height: 50px;
            background: white;
            padding: 3px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .qr-code {
            width: 100%;
            height: 100%;
        }
        
        .qr-code svg {
            width: 100% !important;
            height: 100% !important;
        }
        
        .year-badge {
            position: absolute;
            top: 52px;
            right: 10px;
            background: #10b981;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .watermark {
            position: absolute;
            bottom: 3px;
            left: 10px;
            font-size: 6pt;
            color: #9ca3af;
            font-style: italic;
        }
        
        /* Styles du VERSO */
        .card-verso {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            border: 2px solid #4a5568;
            border-radius: 8px;
            padding: 10px;
            position: relative;
            color: white;
        }
        
        .verso-header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .verso-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }
        
        .school-info {
            background: rgba(255, 255, 255, 0.95);
            padding: 8px;
            border-radius: 6px;
            margin-bottom: 8px;
            color: #1f2937;
        }
        
        .info-line {
            margin-bottom: 3px;
            font-size: 7pt;
            display: flex;
            align-items: flex-start;
        }
        
        .info-icon {
            font-weight: bold;
            margin-right: 5px;
            color: #7c3aed;
            min-width: 12px;
        }
        
        .info-text {
            flex: 1;
        }
        
        .emergency-section {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        
        .emergency-title {
            font-weight: bold;
            color: #fef2f2;
            font-size: 7pt;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .emergency-contact {
            color: #fef2f2;
            font-size: 7pt;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 6px;
        }
        
        .signature-box {
            display: table-cell;
            width: 48%;
            text-align: center;
        }
        
        .signature-box:first-child {
            padding-right: 4px;
        }
        
        .signature-label {
            font-size: 6pt;
            margin-bottom: 2px;
            color: #fef2f2;
        }
        
        .signature-line {
            border-top: 1px solid rgba(255, 255, 255, 0.5);
            height: 25px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
        
        .signature-image {
            max-height: 23px;
            max-width: 100%;
        }
        
        .footer-note {
            position: absolute;
            bottom: 5px;
            left: 10px;
            right: 10px;
            text-align: center;
            font-size: 5pt;
            color: rgba(255, 255, 255, 0.7);
            font-style: italic;
        }
        
        .qr-mini {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 35px;
            height: 35px;
            background: white;
            padding: 2px;
            border-radius: 3px;
        }
        
        .qr-mini svg {
            width: 100% !important;
            height: 100% !important;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- RECTO -->
        <div class="card-side">
            <div class="card-recto">
                <div class="card-header">
                    <div class="logo-section">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}" alt="Logo" class="logo">
                        @endif
                    </div>
                    <div class="header-text">
                        <div class="school-name">{{ $etablissement->nom }}</div>
                        <div class="card-title">CARTE D'IDENTITÉ SCOLAIRE</div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="photo-section">
                        <div class="photo-frame">
                            @if($photoBase64)
                                <img src="{{ $photoBase64 }}" alt="Photo élève" class="photo">
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <div class="info-row">
                            <div class="info-label">Matricule</div>
                            <div class="info-value">
                                <span class="matricule-highlight">{{ $eleve->matricule }}</span>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Nom</div>
                            <div class="info-value">{{ strtoupper($eleve->nom) }}</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Prénom(s)</div>
                            <div class="info-value">{{ ucwords(strtolower($eleve->prenom)) }}</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Né(e) le</div>
                            <div class="info-value">{{ $eleve->date_naissance ? $eleve->date_naissance->format('d/m/Y') : 'N/A' }}</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Lieu</div>
                            <div class="info-value">{{ $eleve->lieu_naissance ?? 'N/A' }}</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Sexe</div>
                            <div class="info-value">{{ $eleve->sexe === 'M' ? 'Masculin' : 'Féminin' }}</div>
                        </div>
                        
                        <div class="info-row">
                            <div class="info-label">Classe</div>
                            <div class="info-value" style="font-weight: bold; color: #7c3aed;">{{ $classe->nom }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="qr-section">
                    <div class="qr-code">{!! $qrCode !!}</div>
                </div>
                
                <div class="year-badge">{{ $anneeAcademique }}</div>
                <div class="watermark">Généré le {{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
        
        <!-- VERSO -->
        <div class="card-side">
            <div class="card-verso">
                <div class="qr-mini">{!! $qrCode !!}</div>
                
                <div class="verso-header">
                    <div class="verso-title">Informations Établissement</div>
                </div>
                
                <div class="school-info">
                    <div class="info-line">
                        <span class="info-icon">📍</span>
                        <span class="info-text">{{ $etablissement->adresse ?? 'Adresse non renseignée' }}, {{ $etablissement->ville ?? '' }}</span>
                    </div>
                    <div class="info-line">
                        <span class="info-icon">📞</span>
                        <span class="info-text">{{ $etablissement->telephone ?? 'Téléphone non renseigné' }}</span>
                    </div>
                    @if($etablissement->email)
                    <div class="info-line">
                        <span class="info-icon">✉️</span>
                        <span class="info-text">{{ $etablissement->email }}</span>
                    </div>
                    @endif
                </div>
                
                <div class="emergency-section">
                    <div class="emergency-title">⚠️ Contact d'urgence</div>
                    <div class="emergency-contact">
                        @if($eleve->nom_parent)
                            <strong>{{ $eleve->nom_parent }}</strong><br>
                        @endif
                        {{ $eleve->contact_parent ?? 'Non renseigné' }}
                    </div>
                </div>
                
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-label">Signature du Responsable</div>
                        <div class="signature-line">
                            @if(isset($signatureResponsable) && $signatureResponsable)
                                <img src="{{ $signatureResponsable }}" alt="Signature" class="signature-image">
                            @endif
                        </div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-label">Signature de l'Élève</div>
                        <div class="signature-line"></div>
                    </div>
                </div>
                
                <div class="footer-note">
                    Cette carte est strictement personnelle et doit être présentée à toute demande.<br>
                    En cas de perte, veuillez contacter immédiatement l'établissement.
                </div>
            </div>
        </div>
    </div>
    
    <div class="instructions">
        ✂️ Découper le long de la ligne pointillée pour séparer le recto et le verso
    </div>
</body>
</html>
