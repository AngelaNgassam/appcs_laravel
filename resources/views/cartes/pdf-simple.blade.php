<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte - {{ $eleve->matricule }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: 85.6mm 53.98mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            width: 85.6mm;
            height: 53.98mm;
            font-family: Arial, sans-serif;
            font-size: 6pt;
            line-height: 1;
        }
        
        .card {
            width: 100%;
            height: 100%;
            border: 2px solid #007a3d;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .header {
            background: linear-gradient(to right, #007a3d 0%, #007a3d 33%, #ce1126 33%, #ce1126 66%, #fcd116 66%, #fcd116 100%);
            height: 8mm;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .header-content {
            background: white;
            padding: 0.8mm 2mm;
            border-radius: 1mm;
            text-align: center;
        }
        
        .star {
            color: #fcd116;
            font-size: 7pt;
            font-weight: bold;
        }
        
        .country {
            font-size: 4.5pt;
            font-weight: bold;
            color: #007a3d;
            text-transform: uppercase;
        }
        
        .motto {
            font-size: 3pt;
            color: #ce1126;
            font-style: italic;
        }
        
        .body {
            flex: 1;
            display: flex;
            padding: 1.5mm;
            gap: 1.5mm;
            position: relative;
        }
        
        .photo {
            width: 15mm;
            height: 19mm;
            border: 1.5px solid #007a3d;
            border-radius: 1mm;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 5pt;
        }
        
        .school {
            text-align: center;
            font-size: 5.5pt;
            font-weight: bold;
            color: #ce1126;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
        }
        
        .row {
            display: flex;
            gap: 0.8mm;
        }
        
        .label {
            font-weight: bold;
            color: #007a3d;
            min-width: 9mm;
            flex-shrink: 0;
        }
        
        .value {
            color: #1f2937;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .badge {
            background: #fcd116;
            padding: 0.2mm 0.5mm;
            border-radius: 0.3mm;
            font-weight: bold;
            color: #000;
            display: inline-block;
            font-size: 5pt;
        }
        
        .class-badge {
            background: #007a3d;
            color: white;
            padding: 0.2mm 0.5mm;
            border-radius: 0.3mm;
            font-weight: bold;
            display: inline-block;
            font-size: 5pt;
        }
        
        .qr {
            position: absolute;
            bottom: 5mm;
            right: 1.5mm;
            width: 10mm;
            height: 10mm;
            background: white;
            border: 1px solid #007a3d;
            border-radius: 0.5mm;
            padding: 0.3mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qr svg {
            width: 100% !important;
            height: 100% !important;
        }
        
        .footer {
            background: #007a3d;
            color: white;
            padding: 0.8mm 1.5mm;
            display: flex;
            justify-content: space-between;
            font-size: 3.5pt;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="header-content">
                <div class="star">★</div>
                <div class="country">République du Cameroun</div>
                <div class="motto">Paix - Travail - Patrie</div>
            </div>
        </div>
        
        <div class="body">
            <div class="photo">
                @if($photoBase64)
                    <img src="{{ $photoBase64 }}" alt="Photo">
                @endif
            </div>
            
            <div class="info">
                <div class="school">{{ $etablissement->nom }}</div>
                
                <div class="row">
                    <div class="label">Matricule :</div>
                    <div class="value"><span class="badge">{{ $eleve->matricule }}</span></div>
                </div>
                
                <div class="row">
                    <div class="label">Nom :</div>
                    <div class="value">{{ strtoupper($eleve->nom) }}</div>
                </div>
                
                <div class="row">
                    <div class="label">Prénom :</div>
                    <div class="value">{{ ucwords(strtolower($eleve->prenom)) }}</div>
                </div>
                
                <div class="row">
                    <div class="label">Né(e) :</div>
                    <div class="value">{{ $eleve->date_naissance ? $eleve->date_naissance->format('d/m/Y') : 'N/A' }}</div>
                </div>
                
                <div class="row">
                    <div class="label">Classe :</div>
                    <div class="value"><span class="class-badge">{{ $classe->nom }}</span></div>
                </div>
            </div>
            
            <div class="qr">
                {!! $qrCode !!}
            </div>
        </div>
        
        <div class="footer">
            <div>Délivré: {{ now()->format('d/m/Y') }}</div>
            <div style="text-align: center; flex: 1;">CARTE OFFICIELLE</div>
            <div style="text-align: right;">Expire: {{ now()->addYears(2)->format('d/m/Y') }}</div>
        </div>
    </div>
</body>
</html>
