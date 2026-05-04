import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EleveModalComponent } from './eleve-modal.component';

describe('EleveModalComponent', () => {
  let component: EleveModalComponent;
  let fixture: ComponentFixture<EleveModalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [EleveModalComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EleveModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
