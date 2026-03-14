<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Carte Scolaire - {{ $eleve->matricule }}</title>
    <style>
        @page { 
            margin: 0; 
            size: 85.6mm 53.98mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7pt;
            line-height: 1.3;
            width: 85.6mm;
            height: 53.98mm;
        }
        
        .card-container {
            width: 100%;
            height: 100%;
            position: relative;
            background: white;
            border: 3px double #2563EB;
        }
        
        .card-header {
            background: linear-gradient(to bottom, #1E40AF 0%, #2563EB 100%);
            padding: 2.5mm 3mm;
            border-bottom: 2px solid #1E3A8A;
            display: table;
            width: 100%;
            color: white;
        }
        
        .header-logo {
            display: table-cell;
            width: 12mm;
            vertical-align: middle;
        }
        
        .logo-img {
            width: 10mm;
            height: 10mm;
            background: white;
            border-radius: 2mm;
            padding: 1mm;
        }
        
        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }
        
        .school-name {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-subtitle {
            font-size: 6pt;
            margin-top: 0.5mm;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 3mm;
            display: table;
            width: 100%;
            height: calc(100% - 12mm - 7mm);
        }
        
        .photo-section {
            display: table-cell;
            width: 25mm;
            vertical-align: top;
        }
        
        .photo-frame {
            width: 22mm;
            height: 28mm;
            border: 2px solid #2563EB;
            overflow: hidden;
            background: #f3f4f6;
        }
        
        .photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info-section {
            display: table-cell;
            vertical-align: top;
            padding: 0 2mm;
        }
        
        .info-row {
            margin-bottom: 1.5mm;
            display: table;
            width: 100%;
            border-bottom: 1px dotted #d1d5db;
            padding-bottom: 1mm;
        }
        
        .info-label {
            font-weight: 600;
            color: #1E40AF;
            font-size: 6pt;
            display: table-cell;
            width: 18mm;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 7pt;
            font-weight: 500;
            display: table-cell;
        }
        
        .matricule-box {
            background: #DBEAFE;
            padding: 1mm 2mm;
            border: 1px solid #2563EB;
            font-weight: bold;
            color: #1E40AF;
            font-size: 7pt;
        }
        
        .classe-badge {
            background: #2563EB;
            color: white;
            padding: 1mm 2mm;
            font-weight: bold;
            font-size: 7pt;
        }
        
        .qr-section {
            display: table-cell;
            width: 20mm;
            vertical-align: top;
            text-align: center;
        }
        
        .qr-frame {
            width: 18mm;
            height: 18mm;
            background: white;
            padding: 1mm;
            border: 2px solid #2563EB;
        }
        
        .qr-code {
            width: 100%;
            height: 100%;
        }
        
        .qr-code svg {
            width: 100% !important;
            height: 100% !important;
        }
        
        .qr-label {
            font-size: 5pt;
            color: #6b7280;
            margin-top: 1mm;
            font-weight: 600;
        }
        
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #1E40AF;
            padding: 1.5mm 3mm;
            display: table;
            width: 100%;
            color: white;
            font-size: 5pt;
        }
        
        .footer-left {
            display: table-cell;
            width: 40%;
        }
        
        .footer-right {
            display: table-cell;
            width: 60%;
            text-align: right;
        }
        
        .year-badge {
            background: white;
            color: #1E40AF;
            padding: 0.5mm 2mm;
            font-weight: bold;
            font-size: 6pt;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="card-header">
            @if($logoBase64)
            <div class="header-logo">
                <img src="{{ $logoBase64 }}" alt="Logo" class="logo-img">
            </div>
            @endif
            <div class="header-text">
                <div class="school-name">{{ $etablissement->nom }}</div>
                <div class="card-subtitle">Carte d'Identité Scolaire</div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="photo-section">
                <div class="photo-frame">
                    @if($photoBase64)
                        <img src="{{ $photoBase64 }}" alt="Photo" class="photo">
                    @endif
                </div>
            </div>
            
            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Matricule :</div>
                    <div class="info-value">
                        <span class="matricule-box">{{ $eleve->matricule }}</span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Nom :</div>
                    <div class="info-value">{{ strtoupper($eleve->nom) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Prénom(s) :</div>
                    <div class="info-value">{{ ucwords(strtolower($eleve->prenom)) }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Né(e) le :</div>
                    <div class="info-value">{{ $eleve->date_naissance ? $eleve->date_naissance->format('d/m/Y') : 'N/A' }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Lieu :</div>
                    <div class="info-value">{{ $eleve->lieu_naissance ?? 'N/A' }}</div>
                </div>
                
                <div class="info-row" style="border-bottom: none;">
                    <div class="info-label">Classe :</div>
                    <div class="info-value">
                        <span class="classe-badge">{{ $classe->nom }}</span>
                    </div>
                </div>
            </div>
            
            <div class="qr-section">
                <div class="qr-frame">
                    <div class="qr-code">{!! $qrCode !!}</div>
                </div>
                <div class="qr-label">{{ $eleve->matricule }}</div>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="footer-left">Délivré le {{ now()->format('d/m/Y') }}</div>
            <div class="footer-right">
                <span class="year-badge">{{ $anneeAcademique }}</span>
            </div>
        </div>
    </div>
</body>
</html>
