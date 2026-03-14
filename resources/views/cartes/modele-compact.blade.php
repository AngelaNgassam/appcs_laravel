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
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
        }
        
        .card-header {
            background: #f59e0b;
            padding: 2mm 3mm;
            display: table;
            width: 100%;
            border-bottom: 2px solid #d97706;
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
        
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 60%;
            color: white;
        }
        
        .school-name {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .card-subtitle {
            font-size: 5pt;
            margin-top: 0.5mm;
        }
        
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 25%;
        }
        
        .year-badge {
            background: white;
            color: #f59e0b;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-size: 7pt;
            font-weight: bold;
        }
        
        .card-body {
            padding: 3mm;
            display: table;
            width: 100%;
            height: calc(100% - 10mm - 6mm);
        }
        
        .photo-section {
            display: table-cell;
            width: 24mm;
            vertical-align: top;
        }
        
        .photo-frame {
            width: 21mm;
            height: 27mm;
            border: 2px solid #f59e0b;
            border-radius: 2mm;
            overflow: hidden;
            background: white;
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
            margin-bottom: 1.2mm;
            display: table;
            width: 100%;
        }
        
        .info-label {
            font-weight: 600;
            color: #92400e;
            font-size: 6pt;
            display: table-cell;
            width: 16mm;
        }
        
        .info-value {
            color: #1f2937;
            font-size: 7pt;
            font-weight: 500;
            display: table-cell;
        }
        
        .matricule-box {
            background: white;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-weight: bold;
            color: #f59e0b;
            border: 1px solid #f59e0b;
            font-size: 7pt;
        }
        
        .classe-badge {
            background: #f59e0b;
            color: white;
            padding: 1mm 2mm;
            border-radius: 2mm;
            font-weight: bold;
            font-size: 7pt;
        }
        
        .qr-section {
            display: table-cell;
            width: 19mm;
            vertical-align: top;
            text-align: center;
        }
        
        .qr-frame {
            width: 17mm;
            height: 17mm;
            background: white;
            padding: 1mm;
            border: 2px solid #f59e0b;
            border-radius: 2mm;
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
            color: #92400e;
            margin-top: 1mm;
            font-weight: 600;
        }
        
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f59e0b;
            padding: 1mm 3mm;
            display: table;
            width: 100%;
            color: white;
            font-size: 5pt;
        }
        
        .footer-left {
            display: table-cell;
            width: 50%;
        }
        
        .footer-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            font-weight: bold;
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
            <div class="header-left">
                <div class="school-name">{{ $etablissement->nom }}</div>
                <div class="card-subtitle">Carte Scolaire</div>
            </div>
            <div class="header-right">
                <div class="year-badge">{{ $anneeAcademique }}</div>
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
                    <div class="info-label">Prénom :</div>
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
                
                <div class="info-row">
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
                <div class="qr-label">QR</div>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="footer-left">{{ now()->format('d/m/Y') }}</div>
            <div class="footer-right">{{ $etablissement->nom }}</div>
        </div>
    </div>
</body>
</html>
